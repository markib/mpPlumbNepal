<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AI\AiStorageService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiStorageServiceTest extends TestCase
{
    public function test_save_result_persists_normalized_payload_and_authenticated_user(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);

        $diagnosis = (new AiStorageService())->saveResult([
            'issue_type' => 'drain_blockage',
            'urgency' => 'medium',
            'price_min' => 700,
            'price_max' => 1800,
            'service' => 'Drain Cleaning',
            'confidence' => 0.75,
            'summary' => 'Drain may be blocked.',
            'raw' => json_encode(['issue_type' => 'drain_blockage', 'confidence' => 0.75]),
            'model' => 'unit-model',
            'prompt_version' => 'v-test',
        ]);

        $this->assertSame($user->id, $diagnosis->user_id);
        $this->assertSame('drain_blockage', $diagnosis->raw['issue_type']);
        $this->assertSame(0.75, $diagnosis->confidence);

        $this->assertDatabaseHas('ai_diagnoses', [
            'id' => $diagnosis->id,
            'user_id' => $user->id,
            'issue_type' => 'drain_blockage',
            'model' => 'unit-model',
        ]);
    }
}
