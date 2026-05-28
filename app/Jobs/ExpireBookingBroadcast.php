<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireBookingBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(BookingBroadcastService $broadcastService): void
    {
        $expiredBookings = Booking::where('broadcast_status', 'broadcasting')
            ->where('broadcast_expires_at', '<=', now())
            ->get();

        foreach ($expiredBookings as $booking) {
            if (!$booking->accepted_by_id) {
                Log::info("Expiring broadcast for booking {$booking->id}");
                $broadcastService->expireBroadcast($booking);
            } else {
                $booking->update(['broadcast_status' => 'assigned']);
            }
        }
    }
}