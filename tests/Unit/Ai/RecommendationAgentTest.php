<?php

namespace Tests\Unit\Ai;

use App\Ai\Agents\Recommendation\RecommendationAgent;
use Tests\TestCase;

class RecommendationAgentTest extends TestCase
{
    public function test_agent_has_instructions(): void
    {
        $agent = new RecommendationAgent();

        $this->assertNotEmpty($agent->instructions());
    }

    public function test_agent_returns_message_structure(): void
    {
        $agent = new RecommendationAgent();

        $this->assertIsIterable($agent->messages());
    }
}
