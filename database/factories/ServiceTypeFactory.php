<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'name' => "Pipe Repair {$sequence}",
            'description' => 'Factory generated plumbing service.',
            'fee' => 500 + ($sequence * 50),
            'is_emergency_available' => true,
        ];
    }
}
