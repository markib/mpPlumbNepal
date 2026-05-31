<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Support\Collection;

class PlumberMatchingService
{
    private array $weights;

    public function __construct()
    {
        $this->weights = config('plumber_match.weights', [
            'distance' => 0.4,
            'rating' => 0.3,
            'response_time' => 0.2,
            'skill_match' => 0.1,
        ]);
    }

    public function matchPlumbersForBooking(Booking $booking): Collection
    {
        $filters = [
            'service_type_ids' => $booking->service_type_id,
            'min_rating' => $booking->min_rating_required,
            'limit' => 50,
        ];

        if ($booking->latitude && $booking->longitude) {
            $geoService = new GeoSearchService;
            $plumbers = $geoService->findNearbyPlumbers(
                $booking->latitude,
                $booking->longitude,
                config('plumber_match.search_radius_km', 15),
                $filters
            );
        } else {
            $plumbers = PlumberProfile::with(['user', 'skills'])
                ->where('is_online', true)
                ->where('is_available', true)
                ->where('verified', true)
                ->where('rating', '>=', $booking->min_rating_required)
                ->when($booking->service_type_id, function ($query) use ($booking) {
                    $query->whereJsonContains('service_type_ids', $booking->service_type_id);
                })
                ->limit(50)
                ->get();
        }

        return $this->sortByMatchScore($plumbers, $booking);
    }

    public function calculateMatchScore(PlumberProfile $plumber, Booking $booking, float $distanceKm): float
    {
        $maxRadiusKm = config('plumber_match.search_radius_km', 15);
        $distanceScore = 1 - min($distanceKm, $maxRadiusKm) / $maxRadiusKm;

        $ratingScore = ($plumber->rating ?? 0) / 5;

        $responseTimeScore = 1.0;
        if ($plumber->available_since) {
            $minutesOnline = now()->diffInMinutes($plumber->available_since);
            $responseTimeScore = 1 / (1 + log1p(max(0, $minutesOnline - 60) / 60));
        }

        $skillMatchScore = 0;
        $bookingSkills = $booking->serviceType?->skills->pluck('id')->toArray() ?? [];
        $plumberSkills = $plumber->skills->pluck('id')->toArray() ?? [];

        if (! empty($bookingSkills) && ! empty($plumberSkills)) {
            $matchingSkills = array_intersect($bookingSkills, $plumberSkills);
            $skillMatchScore = count($matchingSkills) / count($bookingSkills);
        }

        $totalScore = ($this->weights['distance'] * $distanceScore)
            + ($this->weights['rating'] * $ratingScore)
            + ($this->weights['response_time'] * $responseTimeScore)
            + ($this->weights['skill_match'] * $skillMatchScore);

        return round($totalScore, 4);
    }

    public function sortByMatchScore(Collection $plumbers, Booking $booking): Collection
    {
        return $plumbers->map(function ($plumber) use ($booking) {
            $distanceKm = $plumber->distance_km
                ?? $this->calculateDistance(
                    $booking->latitude,
                    $booking->longitude,
                    $plumber->latitude,
                    $plumber->longitude
                );
            $plumber->match_score = $this->calculateMatchScore($plumber, $booking, $distanceKm);

            return $plumber;
        })
            ->sortByDesc('match_score')
            ->values();
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $geoService = new GeoSearchService;

        return $geoService->calculateDistance($lat1, $lng1, $lat2, $lng2);
    }

    public function getTopMatches(Booking $booking, int $count = 10): Collection
    {
        return $this->matchPlumbersForBooking($booking)->take($count);
    }
}
