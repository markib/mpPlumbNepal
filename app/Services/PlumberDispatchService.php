<?php

namespace App\Services;

use App\Ai\Context\PipelineContext;
use App\Ai\Pipeline\PipelineExecutor;
use App\Ai\Workflows\DispatchWorkflow;
use App\Models\Booking;
use App\Services\GeoSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PlumberDispatchService
{
    public function __construct(
        protected PipelineExecutor $executor,
        protected DispatchWorkflow $workflow,
        protected GeoSearchService $geoSearchService
    ) {}

    /**
     * Recommend plumbers for a specific booking using AI dispatch agent.
     *
     * @return array<string, mixed>
     */
    public function recommendForBooking(Booking $booking): array
    {
        $bookingContext = [
            'latitude' => $booking->latitude,
            'longitude' => $booking->longitude,
            'service_type_id' => $booking->service_type_id,
            'is_emergency' => $booking->is_emergency ?? false,
            'min_rating_required' => $booking->min_rating_required ?? config('plumber_match.min_rating', 3.5),
        ];

        return $this->runDispatchPipeline($bookingContext);
    }

    /**
     * Recommend plumbers for a location-based free-form search.
     *
     * @return array<string, mixed>
     */
    public function recommendForLocation(
        float $latitude,
        float $longitude,
        ?int $serviceTypeId = null,
        float $radiusKm = 15,
        bool $isEmergency = false,
        float $minRating = 3.5
    ): array {
        $bookingContext = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'service_type_id' => $serviceTypeId,
            'is_emergency' => $isEmergency,
            'min_rating_required' => $minRating,
            'search_radius_km' => $radiusKm,
        ];

        return $this->runDispatchPipeline($bookingContext);
    }

    /**
     * Run the dispatch AI pipeline.
     *
     * @param array<string, mixed> $bookingContext
     * @return array<string, mixed>
     */
    private function runDispatchPipeline(array $bookingContext): array
    {
        try {
            $context = new PipelineContext([
                'booking_context' => $bookingContext,
            ]);

            $this->executor->execute(
                $this->workflow->steps(),
                $context
            );

            $recommendations = $context->get('dispatch_recommendations');

            Log::info('Dispatch Pipeline Completed', [
                'recommendations_count' => count($recommendations['recommendations'] ?? []),
                'confidence' => $recommendations['confidence'] ?? 0,
            ]);

            return $this->normalizeRecommendations($recommendations);
        } catch (\Throwable $e) {
            Log::error('Dispatch Pipeline Failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to generate plumber recommendations',
                'recommendations' => [],
                'confidence' => 0,
                'summary' => 'Unable to generate recommendations at this time',
                'alternative_notes' => '',
            ];
        }
    }

    /**
     * Normalize and enrich recommendations for API response.
     *
     * @param array<string, mixed> $recommendations
     * @return array<string, mixed>
     */
    private function normalizeRecommendations(array $recommendations): array
    {
        if (! isset($recommendations['recommendations']) || empty($recommendations['recommendations'])) {
            return [
                'status' => 'success',
                'recommendations' => [],
                'confidence' => 0,
                'summary' => 'No plumbers available matching criteria',
            ];
        }

        $normalized = [
            'status' => 'success',
            'recommendations' => array_map(function ($rec) {
                return [
                    'plumber_id' => $rec['plumber_id'],
                    'name' => $rec['name'],
                    'rating' => $rec['rating'],
                    'distance_km' => $rec['distance_km'],
                    'match_score' => $rec['match_score'],
                    'completed_jobs' => $rec['completed_jobs'],
                    'average_rating' => $rec['average_rating'],
                    'skills_matched' => $rec['skills_matched'] ?? [],
                    'is_available' => $rec['is_available'],
                    'recommendation_reason' => $rec['recommendation_reason'],
                    'flags' => $rec['flags'] ?? [],
                ];
            }, $recommendations['recommendations']),
            'confidence' => $recommendations['confidence'] ?? 0,
            'summary' => $recommendations['summary'] ?? '',
            'alternative_notes' => $recommendations['alternative_notes'] ?? '',
        ];

        return $normalized;
    }

    /**
     * Use the dispatch agent pipeline to find nearby plumbers and
     * return a normalized collection suitable for API responses.
     *
     * This returns a Collection of plain arrays (not Eloquent models).
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     id: mixed,
     *     name: mixed,
     *     rating: mixed,
     *     distance_km: mixed,
     *     distance_meters: int|null,
     *     eta_minutes: mixed,
     *     is_online: mixed,
     *     is_available: mixed,
     *     is_verified: bool,
     *     socket_id: mixed,
     *     skills: array<int, mixed>,
     *     user: array<string, mixed>,
     * }>
     */
    public function findNearbyPlumbersUsingAgent(
        float $latitude,
        float $longitude,
        ?int $serviceTypeId = null,
        int $radiusMeters = 5000,
        ?float $minRating = null,
        bool $isEmergency = false
    ): Collection {
        $radiusKm = $radiusMeters / 1000;

        $result = $this->recommendForLocation(
            $latitude,
            $longitude,
            $serviceTypeId,
            $radiusKm,
            $isEmergency,
            $minRating ?? config('plumber_match.min_rating', 3.5)
        );

        /** @var array<int, array<string, mixed>> $recs */
        $recs = $result['recommendations'] ?? [];

        /** @var \Illuminate\Support\Collection<int, array{
         *     id: mixed,
         *     name: mixed,
         *     rating: mixed,
         *     distance_km: mixed,
         *     distance_meters: int|null,
         *     eta_minutes: mixed,
         *     is_online: mixed,
         *     is_available: mixed,
         *     is_verified: bool,
         *     socket_id: mixed,
         *     skills: array<int, mixed>,
         *     user: array<string, mixed>,
         * }> $plumbers */
        $plumbers = collect($recs)->map(function ($r) {
            return [
                'id' => $r['plumber_id'] ?? null,
                'name' => $r['name'] ?? null,
                'rating' => $r['rating'] ?? null,
                'distance_km' => $r['distance_km'] ?? null,
                'distance_meters' => isset($r['distance_km']) ? (int) round($r['distance_km'] * 1000) : null,
                'eta_minutes' => $r['eta_minutes'] ?? null,
                'is_online' => $r['is_online'] ?? null,
                'is_available' => $r['is_available'] ?? null,
                'is_verified' => in_array('verified', $r['flags'] ?? [], true),
                'socket_id' => $r['socket_id'] ?? null,
                'skills' => $r['skills_matched'] ?? [],
                'user' => [
                    'name' => $r['name'] ?? null,
                    'phone' => $r['phone'] ?? null,
                ],
            ];
        })->take(20)->values();

        if ($plumbers->isEmpty()) {
            return $this->fallbackNearbyPlumbers(
                $latitude,
                $longitude,
                $serviceTypeId,
                $radiusKm
            );
        }

        return $plumbers;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     id: mixed,
     *     name: mixed,
     *     rating: mixed,
     *     distance_km: mixed,
     *     distance_meters: mixed,
     *     eta_minutes: mixed,
     *     is_online: mixed,
     *     is_available: mixed,
     *     is_verified: bool,
     *     socket_id: mixed,
     *     skills: array<int, mixed>,
     *     user: array<string, mixed>,
     * }>
     */
    private function fallbackNearbyPlumbers(
        float $latitude,
        float $longitude,
        ?int $serviceTypeId,
        float $radiusKm
    ): Collection {
        Log::warning('Dispatch agent returned no plumbers, falling back to geo search', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_km' => $radiusKm,
            'service_type_id' => $serviceTypeId,
        ]);

        $filters = [];
        if ($serviceTypeId !== null) {
            $filters['service_type_ids'] = [$serviceTypeId];
        }

        /** @var \Illuminate\Support\Collection<int, array{
         *     id: mixed,
         *     name: mixed,
         *     rating: mixed,
         *     distance_km: mixed,
         *     distance_meters: mixed,
         *     eta_minutes: mixed,
         *     is_online: mixed,
         *     is_available: mixed,
         *     is_verified: bool,
         *     socket_id: mixed,
         *     skills: array<int, mixed>,
         *     user: array<string, mixed>,
         * }> $fallbackPlumbers */
        $fallbackPlumbers = $this->geoSearchService->findNearbyPlumbers(
            $latitude,
            $longitude,
            $radiusKm,
            $filters
        )->map(function ($plumber) {
            return [
                'id' => $plumber->id,
                'name' => $plumber->user->name,
                'rating' => $plumber->rating,
                'distance_km' => $plumber->distance_km,
                'distance_meters' => $plumber->distance_meters,
                'eta_minutes' => $plumber->eta_minutes,
                'is_online' => $plumber->is_online,
                'is_available' => $plumber->is_available,
                'is_verified' => $plumber->verified,
                'socket_id' => null,
                'skills' => $plumber->skills->pluck('name')->all(),
                'user' => [
                    'name' => $plumber->user->name,
                    'phone' => $plumber->user->phone,
                ],
            ];
        });

        return $fallbackPlumbers;
    }
}
