<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        ServiceType::firstOrCreate([
            'name' => 'General Plumbing',
        ], [
            'description' => 'Standard plumbing services for household repairs and installations.',
            'fee' => 500,
            'is_emergency_available' => true,
        ]);
    }
}
