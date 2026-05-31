<?php

namespace App\Events;

use App\Models\PlumberProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlumberLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PlumberProfile $plumber,
        public array $locationData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('plumbers.'.$this->plumber->id.'.location'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'plumber' => [
                'id' => $this->plumber->id,
                'name' => $this->plumber->user->name,
            ],
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
