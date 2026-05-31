<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?PlumberProfile $plumber = null
    ) {}

    public function broadcastOn(): array
    {
        if ($this->plumber) {
            return [
                new PrivateChannel('plumbers.'.$this->plumber->id),
            ];
        }

        return [
            new PrivateChannel('plumbers.available'),
        ];
    }

    public function broadcastWith(): array
    {
        $customer = $this->booking->user;
        $serviceType = $this->booking->serviceType;
        $skills = $this->booking->serviceType?->skills->pluck('name')->toArray() ?? [];

        return [
            'booking' => [
                'id' => $this->booking->id,
                'customer_name' => $customer?->name ?? 'Guest',
                'customer_phone' => $customer?->phone,
                'service_type_name' => $serviceType?->name ?? 'Unknown',
                'service_type' => $serviceType?->name ?? 'Unknown',
                'skill_required' => $skills,
                'latitude' => $this->booking->latitude,
                'longitude' => $this->booking->longitude,
                'distance_km' => $this->plumber ? round($this->plumber->pivot->distance_meters ?? 0 / 1000, 2) : null,
                'eta_minutes' => $this->calculateEta(),
                'is_emergency' => $this->booking->is_emergency,
                'amount' => $this->booking->amount,
                'created_at' => $this->booking->created_at->toISOString(),
                'expires_at' => $this->booking->broadcast_expires_at?->toISOString(),
                'min_rating_required' => $this->booking->min_rating_required,
                'landmark' => $this->booking->landmark,
                'ward_number' => $this->booking->ward_number,
                'tole_name' => $this->booking->tole_name,
            ],
        ];
    }

    private function calculateEta(): int
    {
        if (! $this->plumber || ! $this->plumber->latitude || ! $this->plumber->longitude) {
            return 15;
        }

        $distanceMeters = $this->plumber->pivot->distance_meters ?? 0;
        $distanceKm = $distanceMeters / 1000;
        $speedKmH = 30;

        return (int) ceil(($distanceKm / $speedKmH) * 60);
    }
}
