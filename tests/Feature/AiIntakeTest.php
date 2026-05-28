<?php

namespace Tests\Feature;

use App\Models\AiDiagnosis;
use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Ai\AgentRunner;
use Exception;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AiIntakeTest extends TestCase
{
    public function test_ai_analysis_returns_gateway_error_when_ai_service_fails(): void
    {
        $user = User::factory()->customer()->create();

        Sanctum::actingAs($user);

        $runner = Mockery::mock(AgentRunner::class);

        $runner->shouldReceive('run')
            ->once()
            ->andThrow(
                new Exception('Service unreachable')
            );

        $this->app->instance(
            AgentRunner::class,
            $runner
        );

        $response = $this->postJson(
            '/api/v1/ai/diagnose',
            [
                'message' => 'Pipe leaking under kitchen sink',
            ]
        );

        $response->assertStatus(502)
            ->assertJson([
                'status' => 'error',
                'message' => 'Service unreachable',
            ]);
    }

    public function test_ai_diagnose_requires_message_or_image_data(): void
    {
        $user = User::factory()->customer()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/ai/diagnose',
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'message',
                'image_data',
            ]);
    }

    public function test_ai_diagnose_validates_message_length_and_image_name_size(): void
    {
        $user = User::factory()->customer()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/ai/diagnose',
            [
                'message' => 'too short',
                'image_name' => str_repeat('a', 256),
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'message',
                'image_name',
            ]);
    }

    public function test_booking_can_be_prefilled_from_ai_diagnosis(): void
    {
        $customer = User::factory()->customer()->create();

        $serviceType = ServiceType::factory()->create([
            'fee' => 1200,
        ]);

        $diagnosis = AiDiagnosis::factory()->create([
            'user_id' => $customer->id,
            'urgency' => 'high',
            'summary' => 'Burst pipe near the sink.',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson(
            '/api/v1/bookings',
            [
                'service_type_id' => $serviceType->id,
                'latitude' => 27.7172,
                'longitude' => 85.3240,
                'payment_method' => 'cod',
                'is_emergency' => true,
                'service_notes' => $diagnosis->summary,
                'ai_diagnosis_id' => $diagnosis->id,
            ]
        );

        $response->assertCreated()
            ->assertJsonPath(
                'booking.ai_diagnosis_id',
                $diagnosis->id
            )
            ->assertJsonPath(
                'booking.is_emergency',
                true
            );

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ai_diagnosis_id' => $diagnosis->id,
            'service_notes' => 'Burst pipe near the sink.',
            'is_emergency' => true,
        ]);

        $this->assertTrue(
            Booking::first()
                ->aiDiagnosis
                ->is($diagnosis)
        );
    }

    public function test_booking_rejects_unknown_ai_diagnosis_id(): void
    {
        $serviceType = ServiceType::factory()->create();

        $response = $this->postJson(
            '/api/v1/bookings',
            [
                'service_type_id' => $serviceType->id,
                'latitude' => 27.7172,
                'longitude' => 85.3240,
                'payment_method' => 'cod',
                'ai_diagnosis_id' => 99999,
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'ai_diagnosis_id',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
