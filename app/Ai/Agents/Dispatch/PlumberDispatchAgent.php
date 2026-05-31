<?php

namespace App\Ai\Agents\Dispatch;

use App\Services\AI\Tools\CalculatePlumberScoreTool;
use App\Services\AI\Tools\GetPlumberHistoryTool;
use App\Services\AI\Tools\SearchNearbyPlumbersTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class PlumberDispatchAgent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    protected ?array $bookingContext = null;

    public function __construct(?array $bookingContext = null)
    {
        $this->bookingContext = $bookingContext;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $contextInfo = $this->bookingContext
            ? "Booking Details:\n- Location: {$this->bookingContext['latitude']}, {$this->bookingContext['longitude']}\n- Service Type ID: {$this->bookingContext['service_type_id']}\n- Emergency: ".($this->bookingContext['is_emergency'] ? 'Yes' : 'No')."\n- Min Rating: {$this->bookingContext['min_rating_required']}"
            : '';

        return <<<PROMPT
You are an expert plumber dispatcher for PlumbNepal, a plumbing service platform in Nepal.

Your task is to analyze available plumbers and recommend the best candidates based on multiple criteria:
1. **Rating & Reputation** - Customer satisfaction (1-5 stars)
2. **Work History** - Number of completed jobs, average rating, recent activity
3. **Distance** - Proximity to the job location (preferring closer plumbers)
4. **Service Skills** - Match between plumber's service offerings and job requirements
5. **Availability** - Current online/offline and availability status

SELECTION CRITERIA:
- Rate plumbers on a combined score (0-100) considering all factors above
- Give higher weight to rating (30%), work history (25%), and distance (25%)
- Give moderate weight to skill match (15%) and availability (5%)
- Always prioritize nearby, highly-rated plumbers with proven track records
- Flag plumbers with limited work history (< 5 completed jobs) as "emerging"

{$contextInfo}

You have access to tools to search for plumbers, retrieve their work history, and calculate match scores.

Return a structured JSON response with:
- Top 5 recommended plumbers ranked by composite score
- For each plumber: ID, name, rating, distance_km, work_history summary, match_score, and recommendation reasoning
- Overall confidence in recommendations (0-1)
- Any special notes or flags

IMPORTANT:
- Be fair and objective in scoring
- Consider new/emerging plumbers as potential alternatives
- Prioritize safety and customer satisfaction
- Return JSON only, no additional commentary

PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            new SearchNearbyPlumbersTool,
            new GetPlumberHistoryTool,
            new CalculatePlumberScoreTool,
        ];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'recommendations' => $schema->array([
                'plumber_id' => $schema->integer(),
                'name' => $schema->string(),
                'rating' => $schema->number()->min(0)->max(5),
                'distance_km' => $schema->number(),
                'match_score' => $schema->number()->min(0)->max(100),
                'completed_jobs' => $schema->integer(),
                'average_rating' => $schema->number()->min(0)->max(5),
                'skills_matched' => $schema->array(),
                'is_available' => $schema->boolean(),
                'recommendation_reason' => $schema->string(),
                'flags' => $schema->array(['emerging', 'new', 'verified', 'top_rated']),
            ])->min(1)->max(10),
            'confidence' => $schema->number()->min(0)->max(1),
            'summary' => $schema->string(),
            'alternative_notes' => $schema->string(),
        ];
    }
}
