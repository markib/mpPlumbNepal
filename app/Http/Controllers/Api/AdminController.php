<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        $users->getCollection()->transform(function ($user) {
            $data = $user->only(['id', 'name', 'email', 'phone', 'role', 'locale', 'verification_status', 'citizenship_verified', 'created_at']);
            $data['has_plumber_profile'] = $user->role === 'plumber' && $user->plumberProfile !== null;

            return $data;
        });

        return response()->json($users);
    }

    public function verifications(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $request->input('status', 'pending');

        $documents = VerificationDocument::with('user')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($documents);
    }

    public function approveVerification(Request $request, VerificationDocument $document)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['review_notes' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($request, $document) {
            $document->status = 'approved';
            $document->review_notes = $request->input('review_notes');
            $document->save();

            $user = $document->user;
            $allApproved = $user->verificationDocuments()->where('status', '!=', 'approved')->count() === 0;
            if ($allApproved) {
                $user->verification_status = 'verified';
                $user->citizenship_verified = true;
                $user->save();
            }
        });

        return response()->json(['message' => 'Verification approved', 'document' => $document->fresh()->load('user')]);
    }

    public function rejectVerification(Request $request, VerificationDocument $document)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['review_notes' => 'required|string|max:1000']);

        $document->status = 'rejected';
        $document->review_notes = $request->input('review_notes');
        $document->save();

        return response()->json(['message' => 'Verification rejected', 'document' => $document->fresh()->load('user')]);
    }

    public function bookings(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Booking::with(['user', 'serviceType', 'acceptedBy.user']);

        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->input('workflow_status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhere('id', (int) $search);
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        $bookings->getCollection()->transform(function ($booking) {
            return [
                'id' => $booking->id,
                'workflow_status' => $booking->workflow_status,
                'payment_method' => $booking->payment_method,
                'amount' => $booking->amount,
                'is_emergency' => $booking->is_emergency,
                'landmark' => $booking->landmark,
                'ward_number' => $booking->ward_number,
                'tole_name' => $booking->tole_name,
                'created_at' => $booking->created_at?->toIso8601String(),
                'contracted_at' => $booking->contracted_at?->toIso8601String(),
                'service_type' => $booking->serviceType?->only(['id', 'name']),
                'customer' => $booking->user?->only(['id', 'name', 'phone']),
                'plumber' => $booking->acceptedBy?->user?->only(['id', 'name', 'phone']),
            ];
        });

        return response()->json($bookings);
    }
}
