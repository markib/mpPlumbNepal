<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Models\ServiceType;
use App\Services\BookingBroadcastService;
use App\Services\PricingCalculator;
use App\Services\PlumberDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function createBooking(Request $request, PricingCalculator $pricing, BookingBroadcastService $broadcastService, PlumberDispatchService $dispatchService)
    {
        $data = $request->validate([
            'service_type_id' => 'required|integer|exists:service_types,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'landmark' => 'nullable|string|max:255',
            'ward_number' => 'nullable|string|max:50',
            'tole_name' => 'nullable|string|max:100',
            'service_notes' => 'nullable|string|max:1000',
            'ai_diagnosis_id' => 'nullable|integer|exists:ai_diagnoses,id',
            'is_emergency' => 'nullable|boolean',
            'payment_method' => 'required|in:esewa,khalti,ime_pay,cod',
        ]);

        $serviceType = ServiceType::find($data['service_type_id']);
        $customer = $request->user() ?? auth('sanctum')->user();
        $distanceKm = 0; // For estimate only; actual plumber distance is determined during dispatch.
        $pricingData = $pricing->calculate($distanceKm, $serviceType->id, $data['is_emergency'] ?? false);

        $bookingData = [
            'user_id' => $customer?->id,
            'service_type_id' => $serviceType->id,
            'status_id' => 1, // Pending
            'workflow_status' => 'pending',
            'payment_method' => $data['payment_method'],
            'amount' => $pricingData['total'],
            'is_emergency' => $data['is_emergency'] ?? false,
            'landmark' => $data['landmark'] ?? null,
            'ward_number' => $data['ward_number'] ?? null,
            'tole_name' => $data['tole_name'] ?? null,
            'service_notes' => $data['service_notes'] ?? null,
            'ai_diagnosis_id' => $data['ai_diagnosis_id'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ];

        if (DB::getDriverName() === 'pgsql') {
            $bookingData['pickup_location'] = DB::raw("ST_GeogFromText('SRID=4326;POINT({$data['longitude']} {$data['latitude']})')");
        } else {
            $bookingData['pickup_latitude'] = $data['latitude'];
            $bookingData['pickup_longitude'] = $data['longitude'];
        }

        $booking = Booking::create($bookingData);

        $broadcastService->broadcastBooking($booking);

        $nearbyPlumbers = $dispatchService->findNearbyPlumbersUsingAgent(
            $data['latitude'],
            $data['longitude'],
            $serviceType->id
        );

        return response()->json([
            'booking' => $booking->load(['serviceType', 'user']),
            'pricing' => $pricingData,
            'nearby_plumbers' => $nearbyPlumbers,
            'notified_plumbers_count' => $nearbyPlumbers->count(),
            'broadcast_status' => $booking->broadcast_status,
            'broadcast_expires_at' => $booking->broadcast_expires_at?->toISOString(),
            'message' => 'Request sent to nearby plumbers. They will receive your job request in real-time.',
        ], 201);
    }

    public function broadcastStatus(Booking $booking, BookingBroadcastService $broadcastService)
    {
        return response()->json($broadcastService->getBroadcastStatus($booking));
    }

    public function show(Booking $booking)
    {
        return response()->json($booking->load(['status', 'serviceType', 'plumber', 'user']));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status_id' => 'required|integer|exists:booking_statuses,id',
        ]);

        $booking->status_id = $request->input('status_id');
        $booking->save();

        return response()->json(['booking' => $booking]);
    }

    public function track(Booking $booking)
    {
        return response()->json([
            'status' => $booking->status->name,
            'updated_at' => $booking->updated_at,
            'plumber' => $booking->plumber ? [
                'id' => $booking->plumber->id,
                'name' => $booking->plumber->user->name,
                'phone' => $booking->plumber->user->phone,
                'is_available' => $booking->plumber->is_available,
            ] : null,
        ]);
    }

    public function invitePlumber(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->user_id !== $user->id) {
            return response()->json(['message' => 'Not your booking'], 403);
        }

        if (! in_array($booking->workflow_status, ['pending', 'proposed'], true)) {
            return response()->json(['message' => 'Booking is not open for direct invite'], 422);
        }

        $data = $request->validate([
            'plumber_profile_id' => 'required|integer|exists:plumber_profiles,id',
        ]);

        $plumber = PlumberProfile::find($data['plumber_profile_id']);
        if (! $plumber || ! $plumber->is_available || ! $plumber->verified || ! $plumber->is_online) {
            return response()->json(['message' => 'Selected plumber is not currently available'], 422);
        }

        $serviceTypeIds = $plumber->service_type_ids ?? [];
        if (! in_array($booking->service_type_id, $serviceTypeIds, true)) {
            return response()->json(['message' => 'Selected plumber does not serve this service type'], 422);
        }

        $booking->accepted_by_id = $plumber->id;
        $booking->plumber_profile_id = $plumber->id;
        $booking->workflow_status = 'contracted';
        $booking->contract_terms = [
            'base_fee' => $booking->amount,
            'material_cost' => 0,
            'eta_minutes' => 60,
            'details' => ['invited_by_customer' => true],
        ];
        $booking->job_order_json = [
            'booking_id' => $booking->id,
            'customer_id' => $booking->user_id,
            'plumber_profile_id' => $plumber->id,
            'contract_terms' => $booking->contract_terms,
            'created_at' => now()->toIso8601String(),
        ];
        $booking->contract_start_code = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $booking->contracted_at = now();
        $booking->save();

        return response()->json([
            'message' => 'Plumber invited successfully',
            'booking' => $booking,
            'plumber' => [
                'id' => $plumber->id,
                'name' => $plumber->user->name,
                'phone' => $plumber->user->phone,
            ],
        ]);
    }
}
