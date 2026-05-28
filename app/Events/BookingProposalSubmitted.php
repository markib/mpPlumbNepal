<?php

namespace App\Events;

use App\Models\BookingProposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingProposalSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BookingProposal $proposal)
    {
        $this->proposal->loadMissing(['booking.serviceType', 'booking.user', 'plumber.user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->proposal->booking->user_id),
            new PrivateChannel('booking.' . $this->proposal->booking_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.proposal.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->proposal->booking_id,
            'proposal_id' => $this->proposal->id,
            'service_type' => $this->proposal->booking->serviceType?->name,
            'plumber' => [
                'id' => $this->proposal->plumber_profile_id,
                'name' => $this->proposal->plumber->user?->name,
                'rating' => $this->proposal->plumber->rating,
            ],
            'total_cost' => $this->proposal->base_fee + $this->proposal->material_cost,
            'eta_minutes' => $this->proposal->eta_minutes,
            'submitted_at' => $this->proposal->created_at?->toISOString(),
        ];
    }
}
