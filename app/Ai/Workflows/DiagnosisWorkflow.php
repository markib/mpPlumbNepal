<?php

namespace App\Ai\Workflows;

use App\Ai\Steps\GenerateRecommendationStep;
use App\Ai\Steps\RunDiagnosisStep;
use App\Ai\Steps\StoreResultStep;
use App\Ai\Steps\ValidateDiagnosisStep;

class DiagnosisWorkflow
{
    public function steps(): array
    {
        return [
            RunDiagnosisStep::class,
            ValidateDiagnosisStep::class,
            GenerateRecommendationStep::class,
            StoreResultStep::class,
        ];
    }
}
