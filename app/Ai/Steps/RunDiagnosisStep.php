<?php

namespace App\Ai\Steps;

use App\Ai\Agents\Diagnosis\PlumbingDiagnoser;
use App\Ai\Contracts\PipelineStep;
use App\Ai\Context\PipelineContext;
use App\Services\Ai\AgentRunner;
use Illuminate\Support\Facades\Log;

class RunDiagnosisStep implements PipelineStep
{
    public function __construct(
        protected AgentRunner $aiService
    ) {}

    public function handle(
        PipelineContext $context
    ): PipelineContext {
        $analysis = $this->aiService->run(
            agent: PlumbingDiagnoser::make(
                image: $context->get('image')
            ),
            message: $context->get('message'),
            image: $context->get('image')
        );
        Log::info('Diagnosis Step Output', [
            'diagnosis' => $analysis ?? null
        ]);
        $context->set('diagnosis', $analysis);

        return $context;
    }
}
