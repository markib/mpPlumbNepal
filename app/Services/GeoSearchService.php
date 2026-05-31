<?php

namespace App\Services;

use App\Models\PlumberProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeoSearchService
{
    private const EARTH_RADIUS_KM = 6371;

    public function findNearbyPlumbers(
        float $latitude,
        float $longitude,
        float $radiusKm = 15,
        array $filters = []
    ): Collection {
        $query = PlumberProfile::with(['user', 'skills'])
            ->where('is_online', true)
            ->where('is_available', true)
            ->where('verified', true);

        if (! empty($filters['service_type_ids'])) {
            $query->whereJsonContains('service_type_ids', $filters['service_type_ids']);
        }

        if (! empty($filters['skill_ids'])) {
            $query->whereHas('skills', function ($q) use ($filters) {
                $q->whereIn('skills.id', $filters['skill_ids']);
            });
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (DB::getDriverName() === 'pgsql') {
            $point = sprintf('SRID=4326;POINT(%s %s)', $longitude, $latitude);

            return $query
                ->selectRaw('plumber_profiles.*, ST_Distance(location, ST_GeogFromText(?)) AS distance_meters', [$point])
                ->whereRaw('ST_DWithin(location, ST_GeogFromText(?), ?)', [$point, $radiusKm * 1000])
                ->orderBy('distance_meters', 'asc')
                ->limit($filters['limit'] ?? 20)
                ->get()
                ->map(function ($plumber) {
                    $plumber->distance_km = round($plumber->distance_meters / 1000, 2);
                    $plumber->eta_minutes = $this->calculateETA($plumber->distance_km);

                    return $plumber;
                });
        }

        $plumbers = $query->get()
            ->filter(function ($profile) {
                return isset($profile->latitude, $profile->longitude);
            })
            ->map(function ($profile) use ($latitude, $longitude) {
                $distanceMeters = $this->calculateHaversineDistance(
                    $latitude,
                    $longitude,
                    $profile->latitude,
                    $profile->longitude
                );
                $profile->distance_meters = $distanceMeters;
                $profile->distance_km = round($distanceMeters / 1000, 2);
                $profile->eta_minutes = $this->calculateETA($profile->distance_km);

                return $profile;
            })
            ->filter(function ($profile) use ($radiusKm) {
                return $profile->distance_km <= $radiusKm;
            })
            ->sortBy('distance_meters')
            ->take($filters['limit'] ?? 20)
            ->values();

        return $plumbers;
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->calculateHaversineDistance($lat1, $lng1, $lat2, $lng2) / 1000;
    }

    private function calculateHaversineDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * asin(min(1, sqrt($a)));

        return self::EARTH_RADIUS_KM * $c * 1000;
    }

    public function calculateETA(float $distanceKm): int
    {
        $averageSpeedKmh = config('plumber_match.average_speed_kmh', 30);
        $etaMinutes = ceil(($distanceKm / $averageSpeedKmh) * 60);

        return max(1, $etaMinutes);
    }

    public function getPlumbersWithinRadius(
        PlumberProfile $plumber,
        float $radiusKm
    ): Collection {
        return $this->findNearbyPlumbers(
            $plumber->latitude,
            $plumber->longitude,
            $radiusKm
        );
    }
}
