<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;
    public $plumber;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking, PlumberProfile $plumber)
    {
        $this->booking = $booking;
        $this->plumber = $plumber;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bookings.' . $this->booking->id),
            new PrivateChannel('user.' . $this->booking->user_id),
            new PrivateChannel('plumbers.' . $this->plumber->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'booking.assigned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'plumber' => [
                'id' => $this->plumber->id,
                'name' => $this->plumber->user->name,
                'phone' => $this->plumber->user->phone,
                'distance_meters' => $this->booking->contract_terms['distance_meters'] ?? 0,
            ],
            'contract_terms' => $this->booking->contract_terms,
            'assigned_at' => $this->booking->contracted_at->toISOString(),
        ];
    }
}
