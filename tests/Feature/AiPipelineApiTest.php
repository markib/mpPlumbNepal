<?php

namespace Tests\Feature;

use App\Jobs\Ai\RunPipelineJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiPipelineApiTest extends TestCase
{
    public function test_ai_diagnose_creates_pipeline_and_dispatches_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai/diagnose', [
            'message' => 'pipe leaking under sink',
        ]);

        $response->assertAccepted()
            ->assertJsonStructure([
                'status',
                'pipeline_id',
            ]);

        Bus::assertDispatched(RunPipelineJob::class);
    }

    public function test_ai_diagnose_requires_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai/diagnose', [
            'message' => '',
        ]);

        $response->assertStatus(422);
    }
}
