<?php

namespace App\Ai\Steps;

use App\Ai\Agents\Dispatch\PlumberDispatchAgent;
use App\Ai\Context\PipelineContext;
use App\Ai\Contracts\PipelineStep;
use App\Services\AI\AgentRunner;
use Illuminate\Support\Facades\Log;

class RunDispatchAgentStep implements PipelineStep
{
    public function __construct(
        protected AgentRunner $aiService
    ) {}

    public function handle(PipelineContext $context): PipelineContext
    {
        $bookingContext = $context->get('booking_context');

        if (! $bookingContext) {
            Log::error('Missing booking context in dispatch agent step');

            return $context;
        }

        try {
            $agentPrompt = json_encode([
                'location' => $bookingContext,
                'task' => 'Find and recommend the best plumbers for this booking',
            ]);

            $response = $this->aiService->run(
                new PlumberDispatchAgent($bookingContext),
                $agentPrompt
            );

            // Normalize response
            $data = $this->normalizeResponse($response);

            Log::info('Dispatch Agent Step Output', [
                'recommendations_count' => count($data['recommendations'] ?? []),
                'confidence' => $data['confidence'] ?? 0,
            ]);

            $context->set('dispatch_recommendations', $data);
        } catch (\Throwable $e) {
            Log::error('Dispatch Agent Step Failed', [
                'error' => $e->getMessage(),
            ]);

            $context->set('dispatch_recommendations', [
                'recommendations' => [],
                'confidence' => 0,
                'error' => $e->getMessage(),
            ]);
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
