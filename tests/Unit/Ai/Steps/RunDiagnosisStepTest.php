<?php

namespace Tests\Unit\Ai\Steps;

use App\Ai\Context\PipelineContext;
use App\Ai\Steps\RunDiagnosisStep;
use App\Services\AiService;
use Mockery;
use Tests\TestCase;

class RunDiagnosisStepTest extends TestCase
{
    public function test_it_calls_ai_service_and_sets_analysis(): void
    {
        $ai = Mockery::mock(AiService::class);

        $ai->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'issue_type' => 'pipe_leak',
                'confidence' => 0.9,
            ]);

        $step = new RunDiagnosisStep($ai);

        $context = new PipelineContext([
            'message' => 'leak under sink',
        ]);

        $result = $step->handle($context);

        $this->assertEquals(
            'pipe_leak',
            $result->get('analysis')['issue_type']
        );
    }
}
