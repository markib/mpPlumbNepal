<?php

namespace Tests\Feature\Dispatch;

use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting dispatch recommendations for a specific booking.
     */
    public function test_dispatch_recommendations_for_booking(): void
    {
        // Create a customer user
        $customer = User::factory()->customer()->create();

        // Create plumber users and profiles
        $plumber1User = User::factory()->plumber()->create();
        $plumber1 = PlumberProfile::factory()->create([
            'user_id' => $plumber1User->id,
            'rating' => 4.8,
            'is_available' => true,
            'is_online' => true,
            'verified' => true,
        ]);

        $plumber2User = User::factory()->plumber()->create();
        $plumber2 = PlumberProfile::factory()->create([
            'user_id' => $plumber2User->id,
            'rating' => 4.2,
            'is_available' => true,
            'is_online' => true,
            'verified' => true,
        ]);

        // Create service type
        $serviceType = ServiceType::factory()->create();

        // Create booking
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'is_emergency' => false,
            'min_rating_required' => 3.5,
        ]);

        // Authenticate as customer
        $this->actingAs($customer, 'sanctum');

        // Make request to dispatch recommendations endpoint
        $response = $this->getJson("/api/v1/bookings/{$booking->id}/dispatch-recommendations");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'booking_id',
            'dispatch_recommendations' => [
                'status',
                'recommendations' => [
                    '*' => [
                        'plumber_id',
                        'name',
                        'rating',
                        'distance_km',
                        'match_score',
                    ],
                ],
                'confidence',
                'summary',
            ],
            'message',
        ]);

        // Verify the response contains recommendations
        $data = $response->json('dispatch_recommendations');
        $this->assertIsArray($data['recommendations']);
        $this->assertGreaterThanOrEqual(0, count($data['recommendations']));
    }

    /**
     * Test dispatch recommendations endpoint returns error for booking without location.
     */
    public function test_dispatch_recommendations_without_location(): void
    {
        $customer = User::factory()->customer()->create();
        $serviceType = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->actingAs($customer, 'sanctum');

        $response = $this->getJson("/api/v1/bookings/{$booking->id}/dispatch-recommendations");

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Booking does not contain location data');
    }

    /**
     * Test agent search endpoint with location parameters.
     */
    public function test_agent_search_with_location(): void
    {
        $customer = User::factory()->customer()->create();
        $serviceType = ServiceType::factory()->create();

        $this->actingAs($customer, 'sanctum');

        $response = $this->postJson('/api/v1/dispatch/agent-search', [
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'service_type_id' => $serviceType->id,
            'radius_km' => 15,
            'is_emergency' => false,
            'min_rating' => 3.5,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'search_location' => [
                'latitude',
                'longitude',
            ],
            'search_params',
            'dispatch_recommendations' => [
                'status',
                'recommendations',
                'confidence',
                'summary',
            ],
        ]);
    }

    /**
     * Test agent search validation.
     */
    public function test_agent_search_validation(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum');

        // Missing required latitude
        $response = $this->postJson('/api/v1/dispatch/agent-search', [
            'longitude' => 85.3240,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('latitude');
    }

    /**
     * Test agent search for emergency plumbing.
     */
    public function test_agent_search_emergency_plumbing(): void
    {
        $customer = User::factory()->customer()->create();
        $serviceType = ServiceType::factory()->create();

        $this->actingAs($customer, 'sanctum');

        $response = $this->postJson('/api/v1/dispatch/agent-search', [
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'service_type_id' => $serviceType->id,
            'radius_km' => 10,
            'is_emergency' => true,
            'min_rating' => 4.0,
        ]);

        $response->assertStatus(200);

        $searchParams = $response->json('search_params');
        $this->assertTrue($searchParams['is_emergency']);
        $this->assertEquals(4.0, $searchParams['min_rating']);
    }
}
