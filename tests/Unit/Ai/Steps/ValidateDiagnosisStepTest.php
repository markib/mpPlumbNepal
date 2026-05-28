<?php

namespace Tests\Unit\Ai\Steps;

use App\Ai\Agents\Diagnosis\DiagnosisValidator;
use App\Ai\Context\PipelineContext;
use App\Ai\Steps\ValidateDiagnosisStep;
use Tests\TestCase;

class ValidateDiagnosisStepTest extends TestCase
{
    public function test_it_validates_and_normalizes_analysis(): void
    {
        $step = new ValidateDiagnosisStep(
            new DiagnosisValidator()
        );

        $context = new PipelineContext([
            'analysis' => [
                'confidence' => 2,
                'urgency' => 'bad',
                'estimated_price_min' => -50,
                'estimated_price_max' => 100,
            ],
        ]);

        $result = $step->handle($context);

        $this->assertEquals(1.0, $result->get('analysis')['confidence']);
        $this->assertEquals('medium', $result->get('analysis')['urgency']);
    }
}
