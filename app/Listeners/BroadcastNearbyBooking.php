<?php

namespace App\Listeners;

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
