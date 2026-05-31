<?php

namespace App\Ai\Agents\Diagnosis;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class PlumbingDiagnoser implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    protected ?array $image = null;

    protected bool $hasImage = false;

    public function __construct(?array $image = null)
    {
        $this->image = $image;
        $this->hasImage = ! empty($image);
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $role = 'You are an expert plumbing consultant and diagnostician for PlumbNepal in Nepal.';

        $context = $this->hasImage
            ? 'The user has provided both a description and an image. Analyze both carefully. Use the image to confirm visible issues like leaks, corrosion, pipe type, or damage severity.'
            : 'The user has provided only a text description. If the description lacks enough detail to give accurate pricing, lower the confidence and ask for more information in the summary.';

        return <<<INSTRUCTIONS
{$role}

{$context}

Rules:
- Always provide estimates in Nepalese Rupees (NPR).
- Be realistic with pricing based on current Nepal market rates.
- If the query is not related to plumbing, set confidence to 0.0 and explain briefly.
- For safety issues (gas, electrical, sewage backup), recommend urgent professional inspection.
INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'issue_type' => $schema->string()->enum([
                'pipe_leak',
                'drain_blockage',
                'toilet_issue',
                'no_hot_water',
                'water_heater',
                'pipe_burst',
                'faucet_repair',
                'installation',
                'sewage_backup',
                'other']),
            'urgency' => $schema->string()->enum(['low', 'medium', 'high']),
            'estimated_price_min' => $schema->integer(),
            'estimated_price_max' => $schema->integer(),
            'recommended_service' => $schema->string(),
            'confidence' => $schema->number()->min(0)->max(1),
            'summary' => $schema->string(),

        ];
    }
}
