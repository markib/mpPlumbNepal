<?php

namespace App\Services\AI\Tools;

use App\Models\PlumberProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchNearbyPlumbersTool implements Tool
{
    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'search_nearby_plumbers';
    }

    /**
     * Get the tool's description.
     */
    public function description(): string
    {
        return 'Search for available plumbers near a specific location. Returns plumbers within the specified radius with their basic information.';
    }

    /**
     * Get the tool's input schema.
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'latitude' => [
                    'type' => 'number',
                    'description' => 'Latitude of the search location',
                ],
                'longitude' => [
                    'type' => 'number',
                    'description' => 'Longitude of the search location',
                ],
                'radius_km' => [
                    'type' => 'number',
                    'description' => 'Search radius in kilometers (default: 15)',
                ],
                'service_type_id' => [
                    'type' => 'integer',
                    'description' => 'Service type ID to filter by (optional)',
                ],
            ],
            'required' => ['latitude', 'longitude'],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $arguments): string
    {
        $latitude = $arguments['latitude'];
        $longitude = $arguments['longitude'];
        $radiusKm = $arguments['radius_km'] ?? 15;
        $serviceTypeId = $arguments['service_type_id'] ?? null;

        if (DB::getDriverName() === 'pgsql') {
            $point = sprintf('SRID=4326;POINT(%s %s)', $longitude, $latitude);

            $plumbers = PlumberProfile::with('user')
                ->selectRaw(
                    'plumber_profiles.*, ST_Distance(location, ST_GeogFromText(?)) AS distance_meters',
                    [$point]
                )
                ->where('is_available', true)
                ->where('is_online', true)
                ->where('verified', true)
                ->whereHas('user', fn ($query) => $query->where('citizenship_verified', true))
                ->when($serviceTypeId, function ($query, $serviceTypeId) {
                    $query->whereJsonContains('service_type_ids', $serviceTypeId);
                })
                ->whereRaw('ST_DWithin(location, ST_GeogFromText(?), ?)', [$point, $radiusKm * 1000])
                ->orderBy('distance_meters', 'asc')
                ->limit(50)
                ->get();
        } else {
            $plumbers = PlumberProfile::with('user')
                ->where('is_available', true)
                ->where('is_online', true)
                ->where('verified', true)
                ->whereHas('user', fn ($query) => $query->where('citizenship_verified', true))
                ->when($serviceTypeId, function ($query, $serviceTypeId) {
                    $query->whereJsonContains('service_type_ids', $serviceTypeId);
                })
                ->get()
                ->filter(fn ($profile) => isset($profile->latitude, $profile->longitude));

            $distanceFromPoint = function ($profile) use ($latitude, $longitude) {
                $latFrom = deg2rad($latitude);
                $lonFrom = deg2rad($longitude);
                $latTo = deg2rad($profile->latitude);
                $lonTo = deg2rad($profile->longitude);
                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;
                $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

                return 6371000 * 2 * asin(min(1, sqrt($a)));
            };

            $plumbers = $plumbers->map(function ($profile) use ($distanceFromPoint) {
                $profile->distance_meters = (int) round($distanceFromPoint($profile));

                return $profile;
            })
                ->filter(fn ($profile) => $profile->distance_meters <= ($radiusKm * 1000))
                ->sortBy('distance_meters')
                ->take(50)
                ->values();
        }

        $results = $plumbers->map(function ($plumber) {
            $distanceKm = ($plumber->distance_meters ?? 0) / 1000;

            return [
                'plumber_id' => $plumber->id,
                'name' => $plumber->user->name,
                'phone' => $plumber->user->phone,
                'rating' => $plumber->rating ?? 0,
                'distance_km' => round($distanceKm, 2),
                'is_online' => $plumber->is_online,
                'is_available' => $plumber->is_available,
                'verified' => $plumber->verified,
            ];
        })->toArray();

        return json_encode([
            'status' => 'success',
            'count' => count($results),
            'plumbers' => $results,
        ]);
    }

    public function handle(Request $request): string
    {
        return $this->execute($request->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'latitude' => $schema->number()->description('Latitude of the search location')->required(),
            'longitude' => $schema->number()->description('Longitude of the search location')->required(),
            'radius_km' => $schema->number()->description('Search radius in kilometers (default: 15)'),
            'service_type_id' => $schema->integer()->description('Service type ID to filter by (optional)'),
        ];
    }
}
