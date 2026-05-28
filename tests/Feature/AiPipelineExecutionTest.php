<?php

namespace Tests\Feature;

use App\Ai\Pipeline\PipelineExecutor;
use App\Ai\Workflows\DiagnosisWorkflow;
use Tests\TestCase;

class AiPipelineExecutionTest extends TestCase
{
    public function test_full_pipeline_execution(): void
    {
        $executor = new PipelineExecutor();

        $context = new \App\Ai\Context\PipelineContext([
            'message' => 'pipe leaking under sink',
        ]);

        $workflow = new DiagnosisWorkflow();

        $result = $executor->execute(
            $workflow->steps(),
            $context
        );

        $this->assertArrayHasKey('analysis', $result->data);
    }
}
