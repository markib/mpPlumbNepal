<?php

namespace Tests\Feature;

use App\Models\AiDiagnosis;
use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\AiService;
use App\Services\AI\AiStorageService;
use Exception;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AiIntakeTest extends TestCase
{
    // public function test_ai_analysis_saves_high_confidence_result_for_authenticated_user(): void
    // {
    //     $user = User::factory()->customer()->create();
    //     Sanctum::actingAs($user);

    //     $this->mockAiServiceReturning([
    //         'issue_type' => 'pipe_leak',
    //         'urgency' => 'high',
    //         'estimated_price_min' => 500,
    //         'estimated_price_max' => 1500,
    //         'recommended_service' => 'Leak Repair',
    //         'confidence' => 0.9,
    //         'summary' => 'Kitchen pipe is leaking.',
    //         'model' => 'diagnoser-test',
    //     ]);

    //     $response = $this->postJson('/api/v1/ai/diagnose', [
    //         'message' => 'Pipe leaking under kitchen sink',
    //     ]);

    //     $response->assertOk()
    //         ->assertJsonPath('status', 'success')
    //         ->assertJsonPath('data.issue_type', 'pipe_leak')
    //         ->assertJsonPath('ai_diagnosis_id', AiDiagnosis::first()->id);

    //     $this->assertDatabaseHas('ai_diagnoses', [
    //         'user_id' => $user->id,
    //         'issue_type' => 'pipe_leak',
    //         'urgency' => 'high',
    //         'service' => 'Leak Repair',
    //         'model' => 'diagnoser-test',
    //         'prompt_version' => 'v1.0',
    //     ]);

    //     $this->assertSame('pipe_leak', AiDiagnosis::first()->raw['issue_type']);
    // }

    // public function test_ai_analysis_accepts_image_only_request(): void
    // {
    //     $aiService = Mockery::mock(AiService::class);
    //     $aiService->shouldReceive('analyze')
    //         ->once()
    //         ->with('', [
    //             'name' => 'leak.jpg',
    //             'data' => base64_encode('dummy-image-data'),
    //         ])
    //         ->andReturn([
    //             'issue_type' => 'pipe_leak',
    //             'urgency' => 'high',
    //             'estimated_price_min' => 500,
    //             'estimated_price_max' => 1500,
    //             'recommended_service' => 'Leak Repair',
    //             'confidence' => 0.9,
    //             'summary' => 'Kitchen pipe is leaking.',
    //         ]);

    //     $this->app->instance(AiService::class, $aiService);

    //     $response = $this->postJson('/api/v1/ai/diagnose', [
    //         'image_name' => 'leak.jpg',
    //         'image_data' => base64_encode('dummy-image-data'),
    //     ]);

    //     $response->assertOk()
    //         ->assertJsonPath('data.issue_type', 'pipe_leak');
    // }

    // public function test_ai_analysis_does_not_store_low_confidence_result(): void
    // {
    //     $this->mockAiServiceReturning([
    //         'issue_type' => 'other',
    //         'urgency' => 'low',
    //         'estimated_price_min' => 0,
    //         'estimated_price_max' => 0,
    //         'recommended_service' => 'Consultation',
    //         'confidence' => 0.39,
    //         'summary' => 'The request is not clearly plumbing related.',
    //     ]);

    //     $response = $this->postJson('/api/v1/ai/diagnose', [
    //         'message' => 'Please diagnose this unclear household issue',
    //     ]);

    //     $response->assertOk()
    //         ->assertJsonPath('ai_diagnosis_id', null);

    //     $this->assertDatabaseCount('ai_diagnoses', 0);
    // }

    // public function test_ai_analysis_still_returns_success_when_storage_fails(): void
    // {
    //     $this->mockAiServiceReturning([
    //         'issue_type' => 'pipe_leak',
    //         'urgency' => 'medium',
    //         'estimated_price_min' => 800,
    //         'estimated_price_max' => 2000,
    //         'recommended_service' => 'Leak Repair',
    //         'confidence' => 0.8,
    //         'summary' => 'Leak requires inspection.',
    //     ]);

    //     $storage = Mockery::mock(AiStorageService::class);
    //     $storage->shouldReceive('saveResult')
    //         ->once()
    //         ->andThrow(new Exception('database unavailable'));
    //     $this->app->instance(AiStorageService::class, $storage);

    //     $response = $this->postJson('/api/v1/ai/diagnose', [
    //         'message' => 'Pipe leaking behind the bathroom wall',
    //     ]);

    //     $response->assertOk()
    //         ->assertJsonPath('status', 'success')
    //         ->assertJsonPath('ai_diagnosis_id', null);

    //     $this->assertDatabaseCount('ai_diagnoses', 0);
    // }

    public function test_ai_analysis_returns_gateway_error_when_ai_service_fails(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);
        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new Exception('Service unreachable'));

        $this->app->instance(AiService::class, $aiService);

        $response = $this->postJson('/api/v1/ai/diagnose', [
            'message' => 'Pipe leaking under kitchen sink',
        ]);

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
        $response = $this->postJson('/api/v1/ai/diagnose', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message', 'image_data']);
    }

    public function test_ai_diagnose_validates_message_length_and_image_name_size(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai/diagnose', [
            'message' => 'too short',
            'image_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message', 'image_name']);
    }

    public function test_booking_can_be_prefilled_from_ai_diagnosis(): void
    {
        $customer = User::factory()->customer()->create();
        $serviceType = ServiceType::factory()->create(['fee' => 1200]);
        $diagnosis = AiDiagnosis::factory()->create([
            'user_id' => $customer->id,
            'urgency' => 'high',
            'summary' => 'Burst pipe near the sink.',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'payment_method' => 'cod',
            'is_emergency' => true,
            'service_notes' => $diagnosis->summary,
            'ai_diagnosis_id' => $diagnosis->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('booking.ai_diagnosis_id', $diagnosis->id)
            ->assertJsonPath('booking.is_emergency', true);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ai_diagnosis_id' => $diagnosis->id,
            'service_notes' => 'Burst pipe near the sink.',
            'is_emergency' => true,
        ]);

        $this->assertTrue(Booking::first()->aiDiagnosis->is($diagnosis));
    }

    public function test_booking_rejects_unknown_ai_diagnosis_id(): void
    {
        $serviceType = ServiceType::factory()->create();

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'payment_method' => 'cod',
            'ai_diagnosis_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ai_diagnosis_id']);
    }

    private function mockAiServiceReturning(array $result): void
    {
        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn($result);

        $this->app->instance(AiService::class, $aiService);
    }
}
