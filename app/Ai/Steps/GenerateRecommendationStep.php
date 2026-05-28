<?php

namespace App\Ai\Steps;

use App\Ai\Contracts\PipelineStep;
use App\Ai\Context\PipelineContext;
use App\Ai\Agents\Recommendation\RecommendationAgent;
use App\Services\Ai\AgentRunner;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationStep implements PipelineStep
{
    public function __construct(
        protected AgentRunner $aiService
    ) {}
    
    public function handle(PipelineContext $context): PipelineContext
    {
        // ✅ FIX: consistent key
        $analysis = $context->get('diagnosis');

        if (!$analysis) {
            Log::error('Missing diagnosis in recommendation step');
            return $context;
        }

        try {
            $response  = $this->aiService->run(
                RecommendationAgent::make(),
                json_encode($analysis)
            );
            
            // 🔥 NORMALIZE RESPONSE (VERY IMPORTANT)
            $data = $this->normalizeResponse($response);

            Log::info('Recommendation Step Output', [
                'recommendation' => $data
            ]);

            // 👉 Store normalized recommendation separately
            $context->set('recommendation', $data);
        } catch (\Throwable $e) {
            Log::error('Recommendation Step Failed', [
                'error' => $e->getMessage(),
            ]);

            $context->set('recommendation', []);
        }

        return $context;
    }

    /**
     * Normalize AI response into array
     */
    private function normalizeResponse(mixed $response): array
    {
        // Case 1: array already
        if (is_array($response)) {
            return $response;
        }

        // Case 2: Laravel Structured Response
        if (is_object($response)) {

            if (method_exists($response, 'toArray')) {
                return $response->toArray();
            }

            if (isset($response->structured)) {
                return (array) $response->structured;
            }

            if (isset($response->text)) {
                return json_decode($response->text, true) ?? [];
            }
        }

        return [];
    }
}
