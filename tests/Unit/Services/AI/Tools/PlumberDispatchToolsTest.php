<?php

namespace Tests\Unit\Services\AI\Tools;

use App\Models\PlumberProfile;
use App\Models\Review;
use App\Models\User;
use App\Services\AI\Tools\CalculatePlumberScoreTool;
use App\Services\AI\Tools\GetPlumberHistoryTool;
use App\Services\AI\Tools\SearchNearbyPlumbersTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlumberDispatchToolsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test SearchNearbyPlumbersTool executes correctly.
     */
    public function test_search_nearby_plumbers_tool(): void
    {
        // Create plumber users and profiles
        $plumberUser = User::factory()->plumber()->create();
        $plumber = PlumberProfile::factory()->create([
            'user_id' => $plumberUser->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'rating' => 4.5,
            'is_available' => true,
            'is_online' => true,
            'verified' => true,
        ]);

        $tool = new SearchNearbyPlumbersTool;

        $result = json_decode($tool->execute([
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'radius_km' => 15,
        ]), true);

        $this->assertEquals('success', $result['status']);
        $this->assertGreaterThan(0, $result['count']);
        $this->assertIsArray($result['plumbers']);
    }

    /**
     * Test GetPlumberHistoryTool retrieves work history.
     */
    public function test_get_plumber_history_tool(): void
    {
        // Create a plumber
        $plumberUser = User::factory()->plumber()->create();
        $plumber = PlumberProfile::factory()->create([
            'user_id' => $plumberUser->id,
            'rating' => 4.5,
            'verified' => true,
        ]);

        // Create some reviews
        Review::factory()->count(3)->create([
            'plumber_profile_id' => $plumber->id,
            'rating' => 5,
        ]);

        $tool = new GetPlumberHistoryTool;

        $result = json_decode($tool->execute([
            'plumber_id' => $plumber->id,
        ]), true);

        /** @var User $user */
        $user = $plumber->user;

        // 💡 Fixes property.nonObject: Assures PHPStan that the User relation object exists
        $this->assertNotNull($plumber->user);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($plumber->id, $result['plumber_id']);
        $this->assertEquals($user->name, $result['name']);
        $this->assertGreaterThan(0, $result['review_count']);
        $this->assertIsNumeric($result['average_rating']);
        $this->assertIsArray($result['recent_reviews']);
    }

    /**
     * Test GetPlumberHistoryTool with non-existent plumber.
     */
    public function test_get_plumber_history_tool_not_found(): void
    {
        $tool = new GetPlumberHistoryTool;

        $result = json_decode($tool->execute([
            'plumber_id' => 99999,
        ]), true);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('not found', strtolower($result['message']));
    }

    /**
     * Test CalculatePlumberScoreTool composite scoring.
     */
    public function test_calculate_plumber_score_tool(): void
    {
        $plumberUser = User::factory()->plumber()->create();
        $plumber = PlumberProfile::factory()->create([
            'user_id' => $plumberUser->id,
        ]);

        $tool = new CalculatePlumberScoreTool;

        $result = json_decode($tool->execute([
            'plumber_id' => $plumber->id,
            'distance_km' => 5,
            'rating' => 4.5,
            'completed_jobs' => 20,
            'average_job_rating' => 4.6,
            'skill_match_percentage' => 90,
            'is_available' => true,
        ]), true);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($plumber->id, $result['plumber_id']);
        $this->assertIsNumeric($result['composite_score']);
        $this->assertGreaterThanOrEqual(0, $result['composite_score']);
        $this->assertLessThanOrEqual(100, $result['composite_score']);
        $this->assertIsArray($result['breakdown']);
        $this->assertArrayHasKey('rank_factor', $result);
    }

    /**
     * Test CalculatePlumberScoreTool scoring breakdown.
     */
    public function test_calculate_plumber_score_breakdown(): void
    {
        $plumberUser = User::factory()->plumber()->create();
        $plumber = PlumberProfile::factory()->create([
            'user_id' => $plumberUser->id,
        ]);

        $tool = new CalculatePlumberScoreTool;

        $result = json_decode($tool->execute([
            'plumber_id' => $plumber->id,
            'distance_km' => 2,
            'rating' => 5.0,
            'completed_jobs' => 50,
            'average_job_rating' => 4.9,
            'skill_match_percentage' => 100,
            'is_available' => true,
        ]), true);

        // Should have high composite score
        $this->assertGreaterThan(80, $result['composite_score']);
        $this->assertEquals('excellent', $result['rank_factor']);
    }

    /**
     * Test tool input schema validity.
     */
    public function test_tool_schemas(): void
    {
        $searchTool = new SearchNearbyPlumbersTool;
        $historyTool = new GetPlumberHistoryTool;
        $scoreTool = new CalculatePlumberScoreTool;

        // 💡 Fixes method.alreadyNarrowedType: Redundant assertIsString and assertIsArray checks removed
        $this->assertNotEmpty($searchTool->name());
        $this->assertNotEmpty($searchTool->description());

        $this->assertNotEmpty($historyTool->name());
        $this->assertNotEmpty($historyTool->description());

        $this->assertNotEmpty($scoreTool->name());
        $this->assertNotEmpty($scoreTool->description());
    }
}
