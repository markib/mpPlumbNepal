<?php

namespace Database\Factories;

use App\Models\AiDiagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiDiagnosis>
 */
class AiDiagnosisFactory extends Factory
{
    protected $model = AiDiagnosis::class;

    public function definition(): array
    {
        return [
            'issue_type' => 'pipe_leak',
            'urgency' => 'high',
            'price_min' => 500,
            'price_max' => 1500,
            'service' => 'Leak Repair',
            'confidence' => 0.9,
            'summary' => 'Pipe leak under kitchen sink.',
            'raw' => ['issue_type' => 'pipe_leak'],
            'model' => 'test-model',
            'prompt_version' => 'v1.0',
        ];
    }
}
