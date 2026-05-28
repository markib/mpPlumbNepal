<?php

namespace Tests\Unit\Ai\Steps;

use App\Ai\Context\PipelineContext;
use App\Ai\Steps\RunDiagnosisStep;
use App\Services\Ai\AgentRunner;
use Mockery;
use Tests\TestCase;

class RunDiagnosisStepTest extends TestCase
{
    public function test_it_calls_ai_service_and_sets_diagnosis(): void
    {
        $ai = Mockery::mock(AgentRunner::class);

        $ai->shouldReceive('run')
            ->once()
            ->withArgs(function ($agent, $message, $image) {
                return $message === 'leak under sink'
                    && $image === null;
            })
            ->andReturn([
                'issue_type' => 'pipe_leak',
                'confidence' => 0.9,
            ]);

        $step = new RunDiagnosisStep($ai);

        $context = new PipelineContext([
            'message' => 'leak under sink',
            'image' => null,
        ]);

        $result = $step->handle($context);

        $this->assertEquals(
            'pipe_leak',
            $result->get('diagnosis')['issue_type']
        );

        $this->assertEquals(
            0.9,
            $result->get('diagnosis')['confidence']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
