<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingProposal;
use App\Models\PlumberProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingProposal>
 */
class BookingProposalFactory extends Factory
{
    protected $model = BookingProposal::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'plumber_profile_id' => PlumberProfile::factory(),
            'base_fee' => 1000,
            'material_cost' => 250,
            'eta_minutes' => 30,
            'proposal_terms' => ['warranty_days' => 7],
            'status' => 'proposed',
        ];
    }
}
