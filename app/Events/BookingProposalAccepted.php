<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\BookingProposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingProposalAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking, public BookingProposal $proposal)
    {
        $this->booking->loadMissing(['serviceType', 'user', 'acceptedBy.user']);
        $this->proposal->loadMissing('plumber.user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bookings.' . $this->booking->id),
            new PrivateChannel('user.' . $this->booking->user_id),
            new PrivateChannel('plumbers.' . $this->proposal->plumber_profile_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.proposal.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'proposal_id' => $this->proposal->id,
            'workflow_status' => $this->booking->workflow_status,
            'contract_terms' => $this->booking->contract_terms,
            'contract_start_code' => $this->booking->contract_start_code,
            'plumber' => [
                'id' => $this->proposal->plumber_profile_id,
                'name' => $this->proposal->plumber->user?->name,
                'phone' => $this->proposal->plumber->user?->phone,
            ],
            'accepted_at' => $this->booking->contracted_at?->toISOString(),
        ];
    }
}
