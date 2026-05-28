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

class PlumberLocationUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;
    public $plumber;
    public $locationData;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking, PlumberProfile $plumber, array $locationData)
    {
        $this->booking = $booking;
        $this->plumber = $plumber;
        $this->locationData = $locationData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('booking.' . $this->booking->id),
            new PrivateChannel('user.' . $this->booking->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'plumber.location.update';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'plumber_id' => $this->plumber->id,
            'location' => [
                'latitude' => $this->locationData['latitude'],
                'longitude' => $this->locationData['longitude'],
                'accuracy' => $this->locationData['accuracy'] ?? null,
                'speed' => $this->locationData['speed'] ?? null,
                'heading' => $this->locationData['heading'] ?? null,
                'updated_at' => now()->toISOString(),
            ],
        ];
    }
}