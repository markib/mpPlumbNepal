<?php

namespace App\Ai\Steps;

use App\Ai\Agents\Diagnosis\DiagnosisValidator;
use App\Ai\Contracts\PipelineStep;
use App\Ai\Context\PipelineContext;

class ValidateDiagnosisStep implements PipelineStep
{
    public function __construct(
        protected DiagnosisValidator $validator
    ) {}

    public function handle(
        PipelineContext $context
    ): PipelineContext {
        $analysis = $this->validator->handle(
            $context->get('diagnosis', [])
        );

        $context->set('diagnosis', $analysis);

        return $context;
    }
}
