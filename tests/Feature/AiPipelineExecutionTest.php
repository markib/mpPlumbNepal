<?php

namespace Tests\Feature;

use App\Ai\Context\PipelineContext;
use App\Ai\Pipeline\PipelineExecutor;
use App\Ai\Workflows\DiagnosisWorkflow;
use App\Models\AiPipeline;
use App\Models\User;
use App\PipelineStatus;
use App\Services\Ai\AgentRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AiPipelineExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_pipeline_execution(): void
    {
        // 1. Intercept WebSocket events
        Event::fake();

        // 2. Mock or patch your AI service layer if your workflow relies on a service container resolution
        // Alternatively, if your Workflow calls an AI Client Driver, mock that driver directly:
        $this->mock(AgentRunner::class, function ($mock) {
            $mock->shouldReceive('run')
                ->twice()
                ->andReturn([
                    'issue_type' => 'pipe_leak',
                    'confidence' => 0.9,
                    'urgency' => 'medium',
                    'summary' => 'Mocked AI response'
                ]);
        });
        // 3. Create your data models
        $user = User::factory()->create();

        $pipeline = AiPipeline::create([
            'user_id' => $user->id,
            'status' => PipelineStatus::PROCESSING,
            'input' => [
                'message' => 'pipe leaking under sink',
                'image' => null,
            ],
        ]);

        $context = new PipelineContext([
            'pipelineId' => $pipeline->id,
            'message' => 'pipe leaking under sink',
            'image' => null,
        ]);

  
        // 4. Run execution
        $executor = $this->app->make(PipelineExecutor::class);
        $workflow = $this->app->make(DiagnosisWorkflow::class);

        $result = $executor->execute(
            $workflow->steps(),
            $context
        );
        

        // 5. Assertions will now pass instantly (< 50ms) instead of taking 1 minute!
        $this->assertArrayHasKey('diagnosis', $result->data);
        $this->assertEquals('pipe_leak', $result->data['diagnosis']['issue_type']);
    }
}
