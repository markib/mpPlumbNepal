<?php

namespace App\Jobs;

use App\Events\BookingBroadcast;
use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Services\BookingBroadcastService;
use App\Services\PlumberMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastBookingToPlumbers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(PlumberMatchingService $matchingService, BookingBroadcastService $broadcastService): void
    {
        if ($this->booking->broadcast_status === 'assigned' || $this->booking->accepted_by_id) {
            Log::info("Booking {$this->booking->id} already assigned, skipping broadcast");
            return;
        }

        if ($this->booking->broadcast_status === 'expired') {
            Log::info("Booking {$this->booking->id} already expired, skipping broadcast");
            return;
        }

        $matchingPlumbers = $matchingService->matchPlumbersForBooking($this->booking);

        Log::info("Matching plumbers for booking {$this->booking->id}: " . $matchingPlumbers->count() . " found");

        if ($matchingPlumbers->isEmpty()) {
            Log::info("No matching plumbers found for booking {$this->booking->id}");
            Log::info("Booking details - lat: {$this->booking->latitude}, lng: {$this->booking->longitude}, service_type_id: {$this->booking->service_type_id}");
            $this->booking->update(['broadcast_status' => 'no_plumbers']);
            return;
        }

        $topPlumbers = $matchingPlumbers->take(config('plumber_match.max_broadcast_recipients', 20));

        Log::info("Notifying top {$topPlumbers->count()} plumbers for booking {$this->booking->id}");

        $notifiedCount = $broadcastService->notifyPlumbers($this->booking, $topPlumbers);

        Log::info("Broadcast booking {$this->booking->id} to {$notifiedCount} plumbers");

        $this->booking->update([
            'broadcast_status' => 'broadcasting',
            'broadcast_expires_at' => now()->addSeconds(config('plumber_match.broadcast_timeout_seconds', 120)),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to broadcast booking {$this->booking->id}: " . $exception->getMessage());

        $this->booking->update([
            'broadcast_status' => 'failed',
        ]);
    }
}