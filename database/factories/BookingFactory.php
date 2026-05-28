<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $attributes = [
            'user_id' => User::factory()->customer(),
            'service_type_id' => ServiceType::factory(),
            'status_id' => 1,
            'workflow_status' => 'pending',
            'payment_method' => 'cod',
            'amount' => 1000,
            'is_emergency' => false,
            'landmark' => 'Near Patan Durbar Square',
            'ward_number' => '5',
            'tole_name' => 'Mangal Bazaar',
            'service_notes' => 'Kitchen sink leak',
            'latitude' => 27.7172,
            'longitude' => 85.3240,
        ];

        if (DB::getDriverName() === 'pgsql') {
            $attributes['pickup_location'] = DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3240 27.7172)')");
        } else {
            $attributes['pickup_latitude'] = 27.7172;
            $attributes['pickup_longitude'] = 85.3240;
        }

        return $attributes;
    }
}
