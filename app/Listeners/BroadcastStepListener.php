<?php

namespace App\Listeners;

class BroadcastStepListener
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
        broadcast($event);
    }
}
