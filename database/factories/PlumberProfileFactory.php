<?php

namespace Database\Factories;

use App\Models\PlumberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<PlumberProfile>
 */
class PlumberProfileFactory extends Factory
{
    protected $model = PlumberProfile::class;

    public function definition(): array
    {
        $attributes = [
            'user_id' => User::factory()->plumber(),
            'service_type_ids' => [],
            'is_available' => true,
            'is_online' => true,
            'available_since' => now(),
            'availability_notes' => null,
            'verified' => true,
            'rating' => 4.5,
        ];

        $attributes['latitude'] = 27.7172;
        $attributes['longitude'] = 85.3240;

        if (DB::getDriverName() === 'pgsql') {
            $attributes['location'] = DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3240 27.7172)')");
        }

        return $attributes;
    }

    public function unavailable(): static
    {
        return $this->state(fn () => [
            'is_available' => false,
            'is_online' => false,
        ]);
    }
}
