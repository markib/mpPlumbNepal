<?php

namespace Tests\Unit;

use App\Models\ServiceType;
use App\Services\PricingCalculator;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    public function test_calculate_uses_base_fee_and_service_fee()
    {
        $serviceType = ServiceType::create([
            'name' => 'Pipe leak repair',
            'description' => 'Fix leakage in pipe fittings',
            'fee' => 500,
            'is_emergency_available' => true,
        ]);

        $calculator = new PricingCalculator();
        $result = $calculator->calculate(1.2, $serviceType->id, false);

        $this->assertEquals(350, $result['base_fee']);
        $this->assertEquals(500, $result['service_fee']);
        $this->assertEquals(0, $result['distance_fee']);
        $this->assertEquals(0, $result['emergency_surcharge']);
        $this->assertEquals(850, $result['total']);
    }

    public function test_calculate_adds_emergency_surcharge_and_distance_fee()
    {
        $serviceType = ServiceType::create([
            'name' => 'Tank cleaning',
            'description' => 'Deep clean water tank',
            'fee' => 700,
            'is_emergency_available' => true,
        ]);

        $calculator = new PricingCalculator();
        $result = $calculator->calculate(4.3, $serviceType->id, true);

        $this->assertEquals(350, $result['base_fee']);
        $this->assertEquals(700, $result['service_fee']);
        $this->assertEquals(173, $result['distance_fee']);
        $this->assertEquals(500, $result['emergency_surcharge']);
        $this->assertEquals(1723, $result['total']);
    }
}
