<?php

namespace Tests\Unit;

use App\Ai\Agents\Diagnosis\PlumbingDiagnoser;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

class PlumbingDiagnoserAgentTest extends TestCase
{
    public function test_instructions_handle_text_only_diagnosis_context(): void
    {
        $instructions = (string) (new PlumbingDiagnoser)->instructions();

        $this->assertStringContainsString('PlumbNepal in Nepal', $instructions);
        $this->assertStringContainsString('only a text description', $instructions);
        $this->assertStringContainsString('Always provide estimates in Nepalese Rupees', $instructions);
    }

    public function test_instructions_handle_image_context(): void
    {
        $instructions = (string) (new PlumbingDiagnoser(['data' => 'abc']))->instructions();

        $this->assertStringContainsString('both a description and an image', $instructions);
        $this->assertStringContainsString('Analyze both carefully', $instructions);
    }

    public function test_schema_requires_expected_structured_diagnosis_fields(): void
    {
        $schema = (new PlumbingDiagnoser)->schema(new JsonSchemaTypeFactory);

        $this->assertSame([
            'issue_type',
            'urgency',
            'estimated_price_min',
            'estimated_price_max',
            'recommended_service',
            'confidence',
            'summary',
        ], array_keys($schema));

        $this->assertContains('pipe_leak', $schema['issue_type']->toArray()['enum']);
        $this->assertContains('sewage_backup', $schema['issue_type']->toArray()['enum']);
        $this->assertSame(['low', 'medium', 'high'], $schema['urgency']->toArray()['enum']);
        $this->assertSame(0, $schema['confidence']->toArray()['minimum']);
        $this->assertSame(1, $schema['confidence']->toArray()['maximum']);
    }

    public function test_agent_has_no_side_effect_tools_or_conversation_history_by_default(): void
    {
        $agent = new PlumbingDiagnoser;

        $this->assertSame([], iterator_to_array($agent->tools()));
        $this->assertSame([], iterator_to_array($agent->messages()));
    }
}
