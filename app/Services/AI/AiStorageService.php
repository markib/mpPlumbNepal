<?php

namespace App\Services\AI;

use App\Models\AiDiagnosis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiStorageService
{
    /**
     * Persist AI diagnosis result to the database.
     *
     * @param array $data
     * @return AiDiagnosis
     * @throws \Exception
     */
    public function saveResult(array $data): AiDiagnosis
    {
        return DB::transaction(function () use ($data) {
            try {
                return AiDiagnosis::create([
                    'issue_type' => $data['issue_type'],
                    'urgency' => $data['urgency'],
                    'price_min' => $data['price_min'],
                    'price_max' => $data['price_max'],
                    'service' => $data['service'],
                    'confidence' => $data['confidence'],
                    'summary' => $data['summary'],
                    'raw' => json_decode($data['raw'], true) ?? $data['raw'],
                    'model' => $data['model'] ?? 'unknown',
                    'prompt_version' => $data['prompt_version'] ?? 'v1',
                    'user_id' => auth()->id(), // Automatically link to the active user
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to save AI Diagnosis: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
