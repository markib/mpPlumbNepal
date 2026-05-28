<?php

namespace App\Services;

use App\Models\ServiceType;

class PricingCalculator
{
    public function calculate(float $distanceKm, int $serviceTypeId, bool $isEmergency = false): array
    {
        $serviceType = ServiceType::find($serviceTypeId);
        if (! $serviceType) {
            throw new \InvalidArgumentException('Invalid service type provided.');
        }

        $baseFee = config('services.plumbing.base_fee', 350); // NPR base booking fee
        $emergencySurcharge = $isEmergency ? config('services.plumbing.emergency_surcharge', 500) : 0;
        $distanceFee = $this->distanceFee($distanceKm);

        $subTotal = $baseFee + $serviceType->fee + $distanceFee + $emergencySurcharge;
        $tax = round($subTotal * config('services.plumbing.tax_rate', 0.0));
        $total = $subTotal + $tax;

        return [
            'base_fee' => $baseFee,
            'service_fee' => $serviceType->fee,
            'distance_fee' => $distanceFee,
            'emergency_surcharge' => $emergencySurcharge,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    protected function distanceFee(float $distanceKm): int
    {
        if ($distanceKm <= 2) {
            return 0;
        }

        return (int) ceil(($distanceKm - 2) * config('services.plumbing.per_km_fee', 75));
    }
}
