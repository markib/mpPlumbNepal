<?php

namespace App\Jobs;

use App\Events\BookingAccepted;
use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Services\BookingBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBookingAcceptance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public Booking $booking,
        public PlumberProfile $plumber
    ) {}

    public function handle(BookingBroadcastService $broadcastService): void
    {
        if (!$broadcastService->acquireAcceptLock($this->booking, $this->plumber)) {
            Log::info("Could not acquire lock for booking {$this->booking->id}, may already be accepted");
            return;
        }

        try {
            $assigned = $broadcastService->assignPlumber($this->booking, $this->plumber);

            if ($assigned) {
                Log::info("Plumber {$this->plumber->id} accepted booking {$this->booking->id}");
                event(new BookingAccepted($this->booking, $this->plumber));
            }
        } finally {
            $broadcastService->releaseAcceptLock($this->booking);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $broadcastService = app(BookingBroadcastService::class);
        $broadcastService->releaseAcceptLock($this->booking);

        Log::error("Failed to process booking acceptance: " . $exception->getMessage());
    }
}