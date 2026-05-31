<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NearbyBookingRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public PlumberProfile $plumber,
        public int $distanceMeters = 0
    ) {
        $this->booking->loadMissing(['serviceType', 'user']);
        $this->plumber->loadMissing('user');
    }

}
