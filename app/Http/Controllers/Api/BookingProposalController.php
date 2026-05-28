<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingProposalAccepted;
use App\Events\BookingProposalSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingProposal;
use App\Models\PlumberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingProposalController extends Controller
{
    public function openRequests(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || ! $profile->is_available || ! $profile->is_online || ! $profile->verified || ! $user->citizenship_verified) {
            return response()->json(['requests' => []]);
        }

        $coordinates = DB::getDriverName() === 'pgsql'
            ? DB::table('plumber_profiles')
                ->where('id', $profile->id)
                ->selectRaw('ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat')
                ->first()
            : (object) [
                'lng' => $profile->longitude,
                'lat' => $profile->latitude,
            ];

        if (! $coordinates || $coordinates->lng === null || $coordinates->lat === null) {
            return response()->json(['requests' => []]);
        }

        $radius = 10000;

        if (DB::getDriverName() === 'pgsql') {
            $point = sprintf('SRID=4326;POINT(%s %s)', $coordinates->lng, $coordinates->lat);

            $requests = DB::table('bookings')
                ->join('service_types', 'service_types.id', '=', 'bookings.service_type_id')
                ->join('users', 'users.id', '=', 'bookings.user_id')
                ->whereIn('bookings.workflow_status', ['pending', 'proposed'])
                ->whereNull('bookings.accepted_by_id')
                ->when($profile->service_type_ids ?? [], fn ($query, $serviceTypeIds) => $query->whereIn('bookings.service_type_id', $serviceTypeIds))
                ->whereNotExists(function ($query) use ($profile) {
                    $query->select(DB::raw(1))
                        ->from('booking_proposals')
                        ->whereColumn('booking_proposals.booking_id', 'bookings.id')
                        ->where('booking_proposals.plumber_profile_id', $profile->id);
                })
                ->whereRaw('ST_DWithin(bookings.pickup_location, ST_GeogFromText(?), ?)', [$point, $radius])
                ->select(
                    'bookings.id',
                    'bookings.landmark',
                    'bookings.ward_number',
                    'bookings.tole_name',
                    'bookings.created_at',
                    'bookings.latitude',
                    'bookings.longitude',
                    'service_types.name as service_type_name',
                    'users.name as customer_name'
                )
                ->selectRaw('ST_Distance(bookings.pickup_location, ST_GeogFromText(?)) AS distance_meters', [$point])
                ->orderBy('distance_meters')
                ->limit(20)
                ->get();

            return response()->json(['requests' => $requests]);
        }

        $serviceTypeIds = $profile->service_type_ids ?? [];
        $requests = Booking::with(['serviceType', 'user'])
            ->whereIn('workflow_status', ['pending', 'proposed'])
            ->whereNull('accepted_by_id')
            ->when($serviceTypeIds, fn ($query) => $query->whereIn('service_type_id', $serviceTypeIds))
            ->whereDoesntHave('proposals', fn ($query) => $query->where('plumber_profile_id', $profile->id))
            ->get()
            ->filter(fn (Booking $booking) => isset($booking->latitude, $booking->longitude))
            ->map(function (Booking $booking) use ($coordinates) {
                $distanceMeters = $this->distanceMeters((float) $coordinates->lat, (float) $coordinates->lng, (float) $booking->latitude, (float) $booking->longitude);

                return [
                    'id' => $booking->id,
                    'landmark' => $booking->landmark,
                    'ward_number' => $booking->ward_number,
                    'tole_name' => $booking->tole_name,
                    'created_at' => $booking->created_at,
                    'latitude' => $booking->latitude,
                    'longitude' => $booking->longitude,
                    'service_type_name' => $booking->serviceType?->name,
                    'customer_name' => $booking->user?->name,
                    'distance_meters' => $distanceMeters,
                ];
            })
            ->filter(fn (array $booking) => $booking['distance_meters'] <= $radius)
            ->sortBy('distance_meters')
            ->take(20)
            ->values();

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || ! $profile->is_online || ! $profile->is_available || ! $profile->verified || ! $user->citizenship_verified) {
            return response()->json(['message' => 'Plumber not eligible to send proposals'], 403);
        }

        $request->validate([
            'base_fee' => 'required|integer|min:0',
            'material_cost' => 'required|integer|min:0',
            'eta_minutes' => 'required|integer|min:5',
            'proposal_terms' => 'nullable|array',
        ]);

        if ($booking->workflow_status !== 'pending' && $booking->workflow_status !== 'proposed') {
            return response()->json(['message' => 'Booking is no longer open for proposals'], 422);
        }

        $serviceTypeIds = $profile->service_type_ids ?? [];
        if (! in_array($booking->service_type_id, $serviceTypeIds, true)) {
            return response()->json(['message' => 'This request does not match your listed plumbing skills'], 422);
        }

        if (BookingProposal::where('booking_id', $booking->id)->where('plumber_profile_id', $profile->id)->exists()) {
            return response()->json(['message' => 'You have already sent a quote for this request'], 422);
        }

        $proposal = BookingProposal::create([
            'booking_id' => $booking->id,
            'plumber_profile_id' => $profile->id,
            'base_fee' => $request->input('base_fee'),
            'material_cost' => $request->input('material_cost'),
            'eta_minutes' => $request->input('eta_minutes'),
            'proposal_terms' => $request->input('proposal_terms'),
            'status' => 'proposed',
        ]);

        $booking->workflow_status = 'proposed';
        $booking->save();

        broadcast(new BookingProposalSubmitted($proposal))->toOthers();
        // event(new BookingProposalSubmitted($proposal));

        return response()->json(['proposal' => $proposal], 201);
    }

    public function customerProposals(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proposals = BookingProposal::with(['booking.serviceType', 'plumber.user'])
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('workflow_status', 'proposed');
            })
            ->where('status', 'proposed')
            ->get()
            ->map(function (BookingProposal $proposal) {
                $distanceMeters = null;
                if (isset($proposal->booking->latitude, $proposal->booking->longitude, $proposal->plumber->latitude, $proposal->plumber->longitude)) {
                    $distanceMeters = $this->distanceMeters(
                        (float) $proposal->booking->latitude,
                        (float) $proposal->booking->longitude,
                        (float) $proposal->plumber->latitude,
                        (float) $proposal->plumber->longitude
                    );
                }

                $totalCost = $proposal->base_fee + $proposal->material_cost;
                $serviceTypeIds = $proposal->plumber->service_type_ids ?? [];

                return [
                    'id' => $proposal->id,
                    'base_fee' => $proposal->base_fee,
                    'material_cost' => $proposal->material_cost,
                    'total_cost' => $totalCost,
                    'eta_minutes' => $proposal->eta_minutes,
                    'proposal_terms' => $proposal->proposal_terms,
                    'status' => $proposal->status,
                    'created_at' => $proposal->created_at,
                    'distance_meters' => $distanceMeters,
                    'match_score' => $this->proposalMatchScore($proposal, $distanceMeters),
                    'skill_match' => in_array($proposal->booking->service_type_id, $serviceTypeIds, true),
                    'booking' => [
                        'id' => $proposal->booking->id,
                        'service_type_id' => $proposal->booking->service_type_id,
                        'service_type_name' => $proposal->booking->serviceType?->name,
                        'landmark' => $proposal->booking->landmark,
                        'ward_number' => $proposal->booking->ward_number,
                        'tole_name' => $proposal->booking->tole_name,
                    ],
                    'plumber' => [
                        'id' => $proposal->plumber->id,
                        'rating' => $proposal->plumber->rating,
                        'skills' => $serviceTypeIds,
                        'is_online' => $proposal->plumber->is_online,
                        'user' => [
                            'name' => $proposal->plumber->user?->name,
                            'phone' => $proposal->plumber->user?->phone,
                        ],
                    ],
                ];
            })
            ->sortByDesc('match_score')
            ->values();

        return response()->json(['proposals' => $proposals]);
    }

    public function customerJobOrders(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobOrders = Booking::with(['serviceType', 'acceptedBy.user'])
            ->where('user_id', $user->id)
            ->whereIn('workflow_status', ['contracted', 'in_progress', 'completed'])
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'service_type_name' => $booking->serviceType?->name,
                    'workflow_status' => $booking->workflow_status,
                    'contract_terms' => $booking->contract_terms,
                    'job_order' => $booking->job_order_json,
                    'contract_start_code' => $booking->contract_start_code,
                    'plumber' => [
                        'name' => $booking->acceptedBy?->user?->name,
                        'phone' => $booking->acceptedBy?->user?->phone,
                    ],
                    'location' => [
                        'landmark' => $booking->landmark,
                        'ward_number' => $booking->ward_number,
                        'tole_name' => $booking->tole_name,
                    ],
                    'contracted_at' => $booking->contracted_at?->toIso8601String(),
                    'job_started_at' => $booking->job_started_at?->toIso8601String(),
                ];
            });

        return response()->json(['job_orders' => $jobOrders]);
    }

    public function customerPendingRequests(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingRequests = Booking::with(['serviceType'])
            ->where('user_id', $user->id)
            ->whereIn('workflow_status', ['pending', 'proposed', 'broadcasting', 'no_plumbers', 'expired'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'service_type_name' => $booking->serviceType?->name,
                    'workflow_status' => $booking->workflow_status,
                    'broadcast_status' => $booking->broadcast_status,
                    'broadcast_expires_at' => $booking->broadcast_expires_at?->toIso8601String(),
                    'amount' => $booking->amount,
                    'is_emergency' => $booking->is_emergency,
                    'landmark' => $booking->landmark,
                    'ward_number' => $booking->ward_number,
                    'tole_name' => $booking->tole_name,
                    'created_at' => $booking->created_at->toIso8601String(),
                ];
            });

        return response()->json(['pending_requests' => $pendingRequests]);
    }

    public function accept(Request $request, Booking $booking, BookingProposal $proposal)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->user_id !== $user->id) {
            return response()->json(['message' => 'Not your booking'], 403);
        }

        if ($proposal->booking_id !== $booking->id || $proposal->status !== 'proposed') {
            return response()->json(['message' => 'Proposal cannot be accepted'], 422);
        }

        DB::transaction(function () use ($booking, $proposal) {
            BookingProposal::where('booking_id', $booking->id)
                ->where('id', '!=', $proposal->id)
                ->update(['status' => 'expired']);

            $proposal->status = 'accepted';
            $proposal->save();

            $booking->accepted_by_id = $proposal->plumber_profile_id;
            $booking->plumber_profile_id = $proposal->plumber_profile_id;
            $booking->workflow_status = 'contracted';
            $booking->contract_terms = [
                'base_fee' => $proposal->base_fee,
                'material_cost' => $proposal->material_cost,
                'eta_minutes' => $proposal->eta_minutes,
                'details' => $proposal->proposal_terms,
            ];
            $booking->job_order_json = [
                'booking_id' => $booking->id,
                'customer_id' => $booking->user_id,
                'plumber_profile_id' => $proposal->plumber_profile_id,
                'contract_terms' => $booking->contract_terms,
                'created_at' => now()->toIso8601String(),
            ];
            $booking->contract_start_code = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $booking->contracted_at = now();
            $booking->save();
        });

        broadcast(new BookingProposalAccepted($booking->fresh(), $proposal->fresh()))->toOthers();

        return response()->json([
            'message' => 'Deal accepted',
            'job_order' => [
                'booking_id' => $booking->id,
                'contract_terms' => $booking->contract_terms,
                'contract_start_code' => $booking->contract_start_code,
                'assigned_plumber_id' => $booking->accepted_by_id,
            ],
        ]);
    }

    public function assignedJobs(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = $user->plumberProfile;

        if (! $profile) {
            return response()->json(['jobs' => []]);
        }

        $jobs = Booking::with(['user', 'serviceType'])
            ->where(function ($query) use ($profile) {
                $query->where('accepted_by_id', $profile->id)
                    ->orWhere('plumber_profile_id', $profile->id);
            })
            ->whereIn('workflow_status', ['contracted', 'in_progress'])
            ->orderByDesc('contracted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'workflow_status' => $booking->workflow_status,
                    'contract_terms' => $booking->contract_terms,
                    'contract_start_code' => $booking->contract_start_code,
                    'job_order_json' => $booking->job_order_json,
                    'job_started_at' => $booking->job_started_at?->toIso8601String(),
                    'service_type_id' => $booking->service_type_id,
                    'service_type_name' => $booking->serviceType?->name,
                    'landmark' => $booking->landmark,
                    'ward_number' => $booking->ward_number,
                    'tole_name' => $booking->tole_name,
                    'customer_name' => $booking->user?->name,
                    'customer_phone' => $booking->user?->phone,
                    'created_at' => $booking->created_at->toIso8601String(),
                    'contracted_at' => $booking->contracted_at?->toIso8601String(),
                ];
            });

        return response()->json(['jobs' => $jobs]);
    }

    public function startJob(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || $booking->accepted_by_id !== $profile->id) {
            return response()->json(['message' => 'Not assigned to this booking'], 403);
        }

        if ($booking->workflow_status !== 'contracted') {
            return response()->json(['message' => 'Booking is not in a startable state'], 422);
        }

        $request->validate([
            'contract_start_code' => 'required|string|size:4',
        ]);

        if ($booking->contract_start_code !== $request->input('contract_start_code')) {
            return response()->json(['message' => 'Invalid start code'], 422);
        }

        $booking->workflow_status = 'in_progress';
        $booking->job_started_at = now();
        $booking->save();

        return response()->json([
            'message' => 'Job started',
            'job_started_at' => $booking->job_started_at,
            'job_order' => $booking->job_order_json ?? [
                'contract_terms' => $booking->contract_terms,
            ],
        ]);
    }

    public function completeJob(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || $booking->accepted_by_id !== $profile->id) {
            return response()->json(['message' => 'Not assigned to this booking'], 403);
        }

        if ($booking->workflow_status !== 'in_progress') {
            return response()->json(['message' => 'Booking is not currently in progress'], 422);
        }

        $booking->workflow_status = 'completed';
        $booking->save();

        return response()->json([
            'message' => 'Job completed',
            'booking' => $booking,
        ]);
    }

    private function distanceMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): int
    {
        $latFrom = deg2rad($fromLatitude);
        $lonFrom = deg2rad($fromLongitude);
        $latTo = deg2rad($toLatitude);
        $lonTo = deg2rad($toLongitude);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return (int) round(6371000 * 2 * asin(min(1, sqrt($a))));
    }

    private function proposalMatchScore(BookingProposal $proposal, ?int $distanceMeters): int
    {
        $ratingScore = (int) round(($proposal->plumber->rating ?? 0) * 20);
        $etaScore = max(0, 100 - $proposal->eta_minutes);
        $distanceScore = $distanceMeters === null ? 50 : max(0, 100 - (int) floor($distanceMeters / 100));
        $skillScore = in_array($proposal->booking->service_type_id, $proposal->plumber->service_type_ids ?? [], true) ? 100 : 0;

        return (int) round(($ratingScore * 0.35) + ($distanceScore * 0.3) + ($skillScore * 0.25) + ($etaScore * 0.1));
    }
}
