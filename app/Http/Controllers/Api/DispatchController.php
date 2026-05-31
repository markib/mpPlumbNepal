<?php

namespace App\Http\Controllers\Api;

use App\Events\PlumberLocationUpdate;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBookingAcceptance;
use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Services\BookingBroadcastService;
use App\Services\GeoSearchService;
use App\Services\PlumberDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function findNearbyPlumbers(float $latitude, float $longitude, ?int $serviceTypeId = null, int $radius = 5000)
    {
        // Delegate to the PlumberDispatchService which runs the agent-backed search
        $minRating = config('plumber_match.min_rating', 3.5);

        $plumbers = app(\App\Services\PlumberDispatchService::class)
            ->findNearbyPlumbersUsingAgent(
                $latitude,
                $longitude,
                $serviceTypeId,
                $radius,
                $minRating,
                false
            );

        return $plumbers;
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meters' => 'nullable|integer|min:500',
            'service_type_id' => 'nullable|integer|exists:service_types,id',
        ]);

        $radius = $data['radius_meters'] ?? 5000;
        $plumbers = $this->findNearbyPlumbers(
            $data['latitude'],
            $data['longitude'],
            $data['service_type_id'] ?? null,
            $radius
        );

        return response()->json(['data' => $plumbers]);
    }

    public function searchBooking(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'radius_meters' => 'nullable|integer|min:500',
        ]);

        if (! isset($booking->latitude) || ! isset($booking->longitude)) {
            return response()->json(['message' => 'Booking does not contain location data'], 422);
        }

        $radius = $data['radius_meters'] ?? 5000;
        $plumbers = $this->findNearbyPlumbers(
            $booking->latitude,
            $booking->longitude,
            $booking->service_type_id,
            $radius
        );

        return response()->json(['data' => $plumbers]);
    }

    public function updateAvailability(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'is_available' => 'required|boolean',
            'latitude' => 'required_with:is_available|numeric',
            'longitude' => 'required_with:is_available|numeric',
            'availability_notes' => 'nullable|string|max:255',
        ]);

        $profile = $user->plumberProfile;
        if (! $profile) {
            return response()->json(['message' => 'Plumber profile not found'], 404);
        }

        $profile->is_available = $data['is_available'];
        $profile->is_online = $data['is_available'];
        $profile->available_since = $data['is_available'] ? now() : null;
        $profile->availability_notes = $data['availability_notes'] ?? $profile->availability_notes;

        if (isset($data['latitude']) && isset($data['longitude']) && DB::getDriverName() === 'pgsql') {
            $profile->location = DB::raw("ST_GeogFromText('SRID=4326;POINT({$data['longitude']} {$data['latitude']})')");
        } elseif (isset($data['latitude']) && isset($data['longitude'])) {
            $profile->latitude = $data['latitude'];
            $profile->longitude = $data['longitude'];
        }

        $profile->save();

        return response()->json([
            'message' => 'Availability updated successfully',
            'profile' => $profile,
        ]);
    }

    public function updateLocation(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $profile = $user->plumberProfile;
        if (! $profile) {
            return response()->json(['message' => 'Plumber profile not found'], 404);
        }

        if (DB::getDriverName() === 'pgsql') {
            $profile->location = DB::raw("ST_GeogFromText('SRID=4326;POINT({$data['longitude']} {$data['latitude']})')");
        } else {
            $profile->latitude = $data['latitude'];
            $profile->longitude = $data['longitude'];
        }
        $profile->last_location_update = now();
        $profile->location_accuracy = $data['accuracy'] ?? null;
        $profile->current_speed = $data['speed'] ?? null;
        $profile->current_heading = $data['heading'] ?? null;
        $profile->save();

        // Broadcast location update for active bookings
        $activeBookings = Booking::where('accepted_by_id', $profile->id)
            ->whereIn('workflow_status', ['contracted', 'in_progress'])
            ->get();

        foreach ($activeBookings as $booking) {
            broadcast(new PlumberLocationUpdate($booking, $profile, $data))->toOthers();
        }

        return response()->json([
            'message' => 'Location updated successfully',
            'location' => [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'updated_at' => $profile->last_location_update->toISOString(),
            ],
        ]);
    }

    public function getPlumberLocation(Booking $booking)
    {
        /*
    |--------------------------------------------------------------------------
    | CHECK ASSIGNED PLUMBER
    |--------------------------------------------------------------------------
    */

        if (! $booking->accepted_by_id) {
            return response()->json([
                'message' => 'No plumber assigned to this booking',
            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | LOAD PLUMBER
    |--------------------------------------------------------------------------
    */

        $plumber = $booking->acceptedBy;

        if (! $plumber) {
            return response()->json([
                'message' => 'Plumber not found',
            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | GET GPS COORDINATES
    |--------------------------------------------------------------------------
    */

        $coordinates = DB::getDriverName() === 'pgsql'
            ? DB::table('plumber_profiles')
                ->where('id', $plumber->id)
                ->selectRaw('ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat')
                ->first()
            : (object) [
                'lat' => $plumber->latitude,
                'lng' => $plumber->longitude,
            ];

        /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'plumber_id' => $plumber->id,

            'location' => $coordinates ? [
                'latitude' => $coordinates->lat,
                'longitude' => $coordinates->lng,
                'accuracy' => $plumber->location_accuracy,
                'speed' => $plumber->current_speed,
                'heading' => $plumber->current_heading,
                'updated_at' => $plumber->last_location_update?->toISOString(),
            ] : null,

            'is_online' => $plumber->is_online,
            'is_available' => $plumber->is_available,
        ]);
    }

    public function acceptBooking(Request $request, Booking $booking, BookingBroadcastService $broadcastService)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plumber = $user->plumberProfile;
        if (! $plumber) {
            return response()->json(['message' => 'Plumber profile not found'], 404);
        }

        if (! $plumber->is_online || ! $plumber->is_available) {
            return response()->json(['message' => 'Plumber is not available'], 422);
        }

        if ($booking->broadcast_status === 'assigned' || $booking->accepted_by_id) {
            return response()->json(['message' => 'Booking already assigned to another plumber'], 422);
        }

        if ($booking->broadcast_status === 'expired') {
            return response()->json(['message' => 'This booking is no longer available'], 422);
        }

        if ($booking->workflow_status !== 'pending') {
            return response()->json(['message' => 'Booking is not in pending status'], 422);
        }

        ProcessBookingAcceptance::dispatch($booking, $plumber);

        return response()->json([
            'message' => 'Booking acceptance is being processed',
            'booking' => $booking->fresh(),
        ]);
    }

    public function rejectBooking(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plumber = $user->plumberProfile;
        if (! $plumber) {
            return response()->json(['message' => 'Plumber profile not found'], 404);
        }

        return response()->json([
            'message' => 'Booking rejected',
            'booking_id' => $booking->id,
        ]);
    }

    public function getNearbyPlumbers(Request $request, Booking $booking, GeoSearchService $geoService)
    {
        if (! $booking->latitude || ! $booking->longitude) {
            return response()->json(['message' => 'Booking does not contain location data'], 422);
        }

        $data = $request->validate([
            'radius_km' => 'nullable|numeric|min:1|max:50',
        ]);

        $radiusKm = $data['radius_km'] ?? config('plumber_match.search_radius_km', 15);

        $plumbers = $geoService->findNearbyPlumbers(
            $booking->latitude,
            $booking->longitude,
            $radiusKm,
            [
                'service_type_ids' => $booking->service_type_id,
                'min_rating' => $booking->min_rating_required,
            ]
        );

        return response()->json([
            'booking_id' => $booking->id,
            'radius_km' => $radiusKm,
            'plumbers' => $plumbers->map(function ($plumber) {
                return [
                    'id' => $plumber->id,
                    'name' => $plumber->user->name,
                    'phone' => $plumber->user->phone,
                    'rating' => $plumber->rating,
                    'distance_km' => $plumber->distance_km,
                    'eta_minutes' => $plumber->eta_minutes,
                    'skills' => $plumber->skills->pluck('name')->toArray(),
                    'is_online' => $plumber->is_online,
                    'is_available' => $plumber->is_available,
                    'socket_id' => $plumber->socket_id,
                ];
            }),
            'count' => $plumbers->count(),
        ]);
    }

    public function dispatchRecommendations(Request $request, Booking $booking, PlumberDispatchService $dispatchService)
    {
        if (! $booking->latitude || ! $booking->longitude) {
            return response()->json(['message' => 'Booking does not contain location data'], 422);
        }

        $recommendations = $dispatchService->recommendForBooking($booking);

        return response()->json([
            'booking_id' => $booking->id,
            'dispatch_recommendations' => $recommendations,
            'message' => 'AI dispatch recommendations generated',
        ]);
    }

    public function agentSearch(Request $request, PlumberDispatchService $dispatchService)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'service_type_id' => 'nullable|integer|exists:service_types,id',
            'radius_km' => 'nullable|numeric|min:1|max:50',
            'is_emergency' => 'nullable|boolean',
            'min_rating' => 'nullable|numeric|min:1|max:5',
        ]);

        $radiusKm = $data['radius_km'] ?? config('plumber_match.search_radius_km', 15);
        $minRating = $data['min_rating'] ?? config('plumber_match.min_rating', 3.5);
        $isEmergency = $data['is_emergency'] ?? false;

        $recommendations = $dispatchService->recommendForLocation(
            $data['latitude'],
            $data['longitude'],
            $data['service_type_id'] ?? null,
            $radiusKm,
            $isEmergency,
            $minRating
        );

        return response()->json([
            'search_location' => [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ],
            'search_params' => [
                'radius_km' => $radiusKm,
                'service_type_id' => $data['service_type_id'] ?? null,
                'is_emergency' => $isEmergency,
                'min_rating' => $minRating,
            ],
            'dispatch_recommendations' => $recommendations,
        ]);
    }
}
