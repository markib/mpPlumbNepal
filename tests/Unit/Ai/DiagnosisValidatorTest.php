<?php

namespace Tests\Unit\Ai;

use App\Ai\Agents\Diagnosis\DiagnosisValidator;
use Tests\TestCase;

class DiagnosisValidatorTest extends TestCase
{
    public function test_it_normalizes_confidence_and_urgency(): void
    {
        $validator = new DiagnosisValidator();

        $result = $validator->handle([
            'issue_type' => 'pipe_leak',
            'urgency' => 'INVALID',
            'confidence' => 1.5,
            'estimated_price_min' => -100,
            'estimated_price_max' => 500,
            'recommended_service' => null,
            'summary' => 'test',
        ]);

        $this->assertEquals('medium', $result['urgency']);
        $this->assertEquals(1.0, $result['confidence']);
        $this->assertEquals(0, $result['estimated_price_min']);
    }

    public function test_it_detects_emergency_cases(): void
    {
        $validator = new DiagnosisValidator();

        $result = $validator->handle([
            'issue_type' => 'pipe_burst',
            'confidence' => 0.9,
        ]);

        $this->assertEquals('high', $result['urgency']);
        $this->assertTrue($result['is_emergency']);
    }
}
