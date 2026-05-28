<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\NearbyBookingRequested;
use App\Events\Broadcasts\NearbyBookingRequestedBroadcast;

class BroadcastNearbyBooking
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        broadcast(new NearbyBookingRequestedBroadcast(
            $event->booking,
            $event->plumber,
            $event->distanceMeters
        ));
    }
}
