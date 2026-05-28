<?php

namespace Tests\Feature;

use App\Models\AiDiagnosis;
use App\Models\ServiceType;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingFromAiTest extends TestCase
{
    public function test_booking_can_use_ai_diagnosis(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $diagnosis = AiDiagnosis::factory()->create([
            'user_id' => $user->id,
            'summary' => 'Pipe leak',
        ]);

        $service = ServiceType::factory()->create();

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $service->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'payment_method' => 'cod',
            'ai_diagnosis_id' => $diagnosis->id,
        ]);

        $response->assertCreated();
    }

    public function test_booking_rejects_invalid_ai_diagnosis(): void
    {
        $service = ServiceType::factory()->create();

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $service->id,
            'ai_diagnosis_id' => 999999,
        ]);

        $response->assertStatus(422);
    }
}
