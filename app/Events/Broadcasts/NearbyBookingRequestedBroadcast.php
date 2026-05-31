<?php

namespace App\Events\Broadcasts;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NearbyBookingRequestedBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Booking $booking,
        public PlumberProfile $plumber,
        public int $distanceMeters = 0)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('plumbers.'.$this->plumber->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.requested.nearby';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'service_type' => $this->booking->serviceType?->name,
            'customer_name' => $this->booking->user?->name,
            'location' => [
                'latitude' => $this->booking->latitude,
                'longitude' => $this->booking->longitude,
                'landmark' => $this->booking->landmark,
                'ward_number' => $this->booking->ward_number,
                'tole_name' => $this->booking->tole_name,
            ],
            'distance_meters' => $this->distanceMeters,
            'requested_at' => $this->booking->created_at?->toISOString(),
        ];
    }
}
