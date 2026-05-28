<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Files\Image;
use Throwable;

class AgentRunner
{
    public function run(
        object $agent,
        string $message,
        ?array $image = null
    ): array {

        $hasImage = !empty($image['data'] ?? null);
        $promptText = $this->buildPrompt($message, $hasImage, $image);
        $attachments = $this->prepareAttachments($image);
        $priority = config('ai.priority', ['ollama']);

        $attempts = $this->buildAttempts($priority);

        $lastException = null;



        foreach ($attempts as $attempt) {

            $provider = $attempt['provider'];
            $model = $attempt['model'];

            try {

                $response = $agent->prompt(
                    prompt: $promptText,
                    attachments: $attachments,
                    provider: $provider,
                    model: $model,
                    timeout: config(
                        "ai.providers.{$provider}.timeout",
                        90
                    )
                );

                Log::info('AI Agent Success', [
                    'agent' => class_basename($agent),
                    'provider' => $provider,
                    'model' => $model,
                ]);

                return $this->normalizeResponse(
                    $response,
                    $provider,
                    $model
                );
            } catch (Throwable $e) {

                $lastException = $e;

                Log::warning('AI Agent Failed', [
                    'agent' => class_basename($agent),
                    'provider' => $provider,
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        throw $lastException;
    }

    private function buildAttempts(array $priority): array
    {
        $attempts = [];

        foreach ($priority as $provider) {

            $defaultModel =
                $this->getDefaultModel($provider);

            switch ($provider) {

                case 'ollama':

                    $attempts[] = [
                        'provider' => 'ollama',
                        'model' => $defaultModel,
                    ];

                    $attempts[] = [
                        'provider' => 'ollama',
                        'model' => config(
                            'ai.providers.ollama.models.text.cheapest'
                        ),
                    ];

                    break;

                case 'gemini':

                    $attempts[] = [
                        'provider' => 'gemini',
                        'model' => $defaultModel,
                    ];

                    break;

                case 'openai':

                    $attempts[] = [
                        'provider' => 'openai',
                        'model' => $defaultModel,
                    ];

                    break;
            }
        }

        return $attempts;
    }

    private function getDefaultModel(
        string $provider
    ): ?string {

        return match ($provider) {

            'ollama' => config(
                'ai.providers.ollama.models.text.default'
            ),

            default => config(
                "ai.providers.{$provider}.model"
            ),
        };
    }

    private function normalizeResponse(
        mixed $response,
        string $provider,
        string $model
    ): array {

        if ($response instanceof StructuredTextResponse) {

            $result =
                $response->structured ?? [];
        } elseif (
            method_exists($response, 'toArray')
        ) {

            $result = $response->toArray();
        } else {

            $result =
                json_decode(
                    $response->text ?? '',
                    true
                ) ?? [];
        }

        return [
            ...$result,
            'provider' => $provider,
            'model' => $model,
        ];
    }

    // ====================== Other Helper Methods ====================== 
    private function buildPrompt(string $message, bool $hasImage, ?array $image): string
    {
        $prompt = "USER DESCRIPTION: " . ($message ?: 'No description provided.');
        if ($hasImage) {
            $prompt .= "\n\nIMAGE ATTACHED: Yes";
        }
        return $prompt;
    }
    private function prepareAttachments(?array $image): array
    {
        if (empty($image['data'] ?? null)) {
            return [];
        }
        $data = $image['data'];
        if (str_contains($data, 'base64,')) {
            $data = explode('base64,', $data, 2)[1] ?? $data;
        }
        if (base64_decode($data, true) === false) {
            $data = base64_encode($data);
        }
        return [Image::fromBase64($data, $image['mime'] ?? 'image/jpeg')];
    }

 
}
