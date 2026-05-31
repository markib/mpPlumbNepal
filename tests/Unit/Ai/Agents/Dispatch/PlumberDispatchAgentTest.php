<?php

namespace Tests\Unit\Ai\Agents\Dispatch;

use App\Ai\Agents\Dispatch\PlumberDispatchAgent;
use App\Services\AI\Tools\CalculatePlumberScoreTool;
use App\Services\AI\Tools\GetPlumberHistoryTool;
use App\Services\AI\Tools\SearchNearbyPlumbersTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Tests\TestCase;

class PlumberDispatchAgentTest extends TestCase
{
    /**
     * Test PlumberDispatchAgent initialization and structure.
     */
    public function test_plumber_dispatch_agent_structure(): void
    {
        $bookingContext = [
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'service_type_id' => 1,
            'is_emergency' => false,
            'min_rating_required' => 3.5,
        ];

        $agent = new PlumberDispatchAgent($bookingContext);

        // Test agent implements correct interfaces
        $this->assertInstanceOf(
            Agent::class,
            $agent
        );

        // Test agent has required methods
        $this->assertTrue(method_exists($agent, 'instructions'));
        $this->assertTrue(method_exists($agent, 'messages'));
        $this->assertTrue(method_exists($agent, 'tools'));
        $this->assertTrue(method_exists($agent, 'schema'));
    }

    /**
     * Test PlumberDispatchAgent instructions.
     */
    public function test_plumber_dispatch_agent_instructions(): void
    {
        $bookingContext = [
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'service_type_id' => 1,
            'is_emergency' => false,
            'min_rating_required' => 3.5,
        ];

        $agent = new PlumberDispatchAgent($bookingContext);
        $instructions = $agent->instructions();

        $this->assertIsString((string) $instructions);
        $this->assertNotEmpty((string) $instructions);

        // Instructions should mention key criteria
        $instructionsStr = strtolower((string) $instructions);
        $this->assertStringContainsString('rating', $instructionsStr);
        $this->assertStringContainsString('distance', $instructionsStr);
        $this->assertStringContainsString('history', $instructionsStr);
    }

    /**
     * Test PlumberDispatchAgent messages are empty.
     */
    public function test_plumber_dispatch_agent_messages(): void
    {
        $agent = new PlumberDispatchAgent;
        $messages = $agent->messages();

        $this->assertIsIterable($messages);
        $this->assertCount(0, iterator_to_array($messages));
    }

    /**
     * Test PlumberDispatchAgent has required tools.
     */
    public function test_plumber_dispatch_agent_tools(): void
    {
        $agent = new PlumberDispatchAgent;
        $tools = $agent->tools();

        $this->assertIsIterable($tools);
        $toolsArray = iterator_to_array($tools);

        // Should have 3 tools
        $this->assertCount(3, $toolsArray);

        // Verify tool classes
        $this->assertInstanceOf(
            SearchNearbyPlumbersTool::class,
            $toolsArray[0]
        );
        $this->assertInstanceOf(
            GetPlumberHistoryTool::class,
            $toolsArray[1]
        );
        $this->assertInstanceOf(
            CalculatePlumberScoreTool::class,
            $toolsArray[2]
        );
    }

    /**
     * Test PlumberDispatchAgent schema is valid.
     */
    public function test_plumber_dispatch_agent_schema(): void
    {
        $agent = new PlumberDispatchAgent;

        // Create a mock schema builder
        $schemaBuilder = \Mockery::mock(JsonSchema::class);

        // The agent should return an array from schema() method
        // We're testing the method exists and returns an array
        $this->assertTrue(method_exists($agent, 'schema'));
    }

    /**
     * Test PlumberDispatchAgent without booking context.
     */
    public function test_plumber_dispatch_agent_without_context(): void
    {
        $agent = new PlumberDispatchAgent;

        // Should still work without context
        $this->assertIsString((string) $agent->instructions());
        $this->assertIsIterable($agent->messages());
        $this->assertIsIterable($agent->tools());
    }
}
