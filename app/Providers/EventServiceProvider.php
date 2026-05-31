<?php

namespace App\Providers;

use App\Events\NearbyBookingRequested;
use App\Listeners\BroadcastNearbyBooking;
use App\Listeners\BroadcastStepListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NearbyBookingRequested::class => [
            BroadcastNearbyBooking::class,
            BroadcastStepListener::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
