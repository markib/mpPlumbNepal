<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public PlumberProfile $plumber
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bookings.' . $this->booking->id),
            new PrivateChannel('plumbers.' . $this->plumber->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'workflow_status' => $this->booking->workflow_status,
                'accepted_by' => [
                    'id' => $this->plumber->id,
                    'name' => $this->plumber->user->name,
                    'phone' => $this->plumber->user->phone,
                    'rating' => $this->plumber->rating,
                ],
                'accepted_at' => now()->toISOString(),
            ],
        ];
    }
}