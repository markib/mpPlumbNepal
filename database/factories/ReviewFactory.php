<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory()->customer(),
            'plumber_profile_id' => PlumberProfile::factory(),
            'rating' => 5,
            'comment' => 'Excellent service',
        ];
    }
}
