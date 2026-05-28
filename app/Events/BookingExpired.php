<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bookings.' . $this->booking->id),
            new PrivateChannel('plumbers.available'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'broadcast_status' => 'expired',
                'expired_at' => now()->toISOString(),
                'message' => 'No plumber accepted this booking. Please try again.',
            ],
        ];
    }
}