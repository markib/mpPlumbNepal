<?php

namespace App\Services;

use App\Events\BookingBroadcast;
use App\Events\BookingAssigned;
use App\Events\BookingExpired;
use App\Events\PlumberLocationUpdated;
use App\Jobs\BroadcastBookingToPlumbers;
use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingBroadcastService
{
    private int $broadcastTimeoutSeconds;

    public function __construct()
    {
        $this->broadcastTimeoutSeconds = config('plumber_match.broadcast_timeout_seconds', 120);
    }

    public function broadcastBooking(Booking $booking): void
    {
        $booking->update([
            'broadcast_status' => 'broadcasting',
            'broadcast_expires_at' => now()->addSeconds($this->broadcastTimeoutSeconds),
        ]);

        BroadcastBookingToPlumbers::dispatch($booking);
    }

    public function assignPlumber(Booking $booking, PlumberProfile $plumber): bool
    {
        if ($booking->workflow_status === 'contracted' || $booking->accepted_by_id) {
            return false;
        }

        $booking->update([
            'accepted_by_id' => $plumber->id,
            'plumber_profile_id' => $plumber->id,
            'workflow_status' => 'contracted',
            'contracted_at' => now(),
            'contract_start_code' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'broadcast_status' => 'assigned',
        ]);

        event(new BookingAssigned($booking, $plumber));

        return true;
    }

    public function expireBroadcast(Booking $booking): void
    {
        $booking->update([
            'broadcast_status' => 'expired',
        ]);

        event(new BookingExpired($booking));
    }

    public function acquireAcceptLock(Booking $booking, PlumberProfile $plumber): bool
    {
        $lockKey = "booking_accept_{$booking->id}";
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return false;
        }

        $booking->refresh();

        if ($booking->broadcast_status === 'assigned' || $booking->accepted_by_id) {
            $lock->release();
            return false;
        }

        return true;
    }

    public function releaseAcceptLock(Booking $booking): void
    {
        $lockKey = "booking_accept_{$booking->id}";
        $lock = Cache::lock($lockKey, 10);
        $lock->release();
    }

    public function getMatchingPlumbers(Booking $booking): \Illuminate\Support\Collection
    {
        $matchingService = new PlumberMatchingService();
        return $matchingService->matchPlumbersForBooking($booking);
    }

    public function notifyPlumbers(Booking $booking, \Illuminate\Support\Collection $plumbers): int
    {
        $count = 0;

        foreach ($plumbers as $plumber) {
            event(new BookingBroadcast($booking, $plumber));
            $count++;
            \Illuminate\Support\Facades\Log::info("Broadcasting booking {$booking->id} to plumber {$plumber->id}");
        }

        return $count;
    }

    public function updatePlumberLocation(PlumberProfile $plumber, array $locationData): void
    {
        $activeBookings = Booking::where('accepted_by_id', $plumber->id)
            ->whereIn('workflow_status', ['contracted', 'in_progress'])
            ->get();

        foreach ($activeBookings as $booking) {
            event(new PlumberLocationUpdated($plumber, $locationData));
        }
    }

    public function getBroadcastStatus(Booking $booking): array
    {
        $booking->refresh();

        return [
            'broadcast_status' => $booking->broadcast_status,
            'expires_at' => $booking->broadcast_expires_at?->toISOString(),
            'expires_in_seconds' => $booking->broadcast_expires_at
                ? max(0, $booking->broadcast_expires_at->diffInSeconds(now()))
                : null,
            'accepted_by' => $booking->accepted_by_id ? [
                'id' => $booking->acceptedBy->id,
                'name' => $booking->acceptedBy->user->name,
                'phone' => $booking->acceptedBy->user->phone,
            ] : null,
        ];
    }
}