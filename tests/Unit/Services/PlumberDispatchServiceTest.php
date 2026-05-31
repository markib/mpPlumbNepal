<?php

namespace Tests\Unit\Services;

use App\Ai\Pipeline\PipelineExecutor;
use App\Ai\Workflows\DispatchWorkflow;
use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\GeoSearchService;
use App\Services\PlumberDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PlumberDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test PlumberDispatchService initialization.
     */
    public function test_service_initialization(): void
    {
        $executor = $this->app->make(PipelineExecutor::class);
        $workflow = $this->app->make(DispatchWorkflow::class);

        $geoSearchService = $this->app->make(GeoSearchService::class);
        $service = new PlumberDispatchService($executor, $workflow, $geoSearchService);

        $this->assertInstanceOf(PlumberDispatchService::class, $service);
    }

    /**
     * Test recommendForBooking method structure.
     */
    public function test_recommend_for_booking_returns_valid_structure(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $serviceType = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'is_emergency' => false,
            'min_rating_required' => 3.5,
        ]);

        $workflow = $this->app->make(DispatchWorkflow::class);

        // 💡 Refactored to native Laravel mock helper to maintain correct type instances
        /** @var PipelineExecutor&MockInterface $executor */
        $executor = $this->mock(PipelineExecutor::class, function ($mock) {
            $mock->shouldReceive('execute')->andReturnUsing(
                function ($steps, $context) {
                    $context->set('dispatch_recommendations', [
                        'recommendations' => [],
                        'confidence' => 0.8,
                        'summary' => 'Test recommendations',
                    ]);

                    // Return the context object back to prevent internal failures if chained
                    return $context;
                }
            );
        });

        $geoSearchService = $this->app->make(GeoSearchService::class);
        $service = new PlumberDispatchService($executor, $workflow, $geoSearchService);
        $result = $service->recommendForBooking($booking);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * Test recommendForLocation method structure.
     */
    public function test_recommend_for_location_returns_valid_structure(): void
    {
        /** @var PipelineExecutor&MockInterface $executor */
        $executor = $this->mock(PipelineExecutor::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->andThrow(new \Exception('Pipeline test'));
        });

        /** @var DispatchWorkflow&MockInterface $workflow */
        $workflow = $this->mock(DispatchWorkflow::class);

        $geoSearchService = $this->app->make(GeoSearchService::class);
        $service = new PlumberDispatchService($executor, $workflow, $geoSearchService);

        $result = $service->recommendForLocation(
            27.7172, // Kathmandu Latitude
            85.3240, // Kathmandu Longitude
            null,
            15,
            false,
            3.5
        );

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertIsArray($result['recommendations']); // This stays! testing a specific nested key array structure is valid
        $this->assertEmpty($result['recommendations']);
    }

    /**
     * Test service handles errors gracefully.
     */
    public function test_service_handles_errors(): void
    {

        $workflow = $this->app->make(DispatchWorkflow::class);

        // 💡 Refactored from raw Mockery to container mock helper
        /** @var PipelineExecutor&MockInterface $executor */
        $executor = $this->mock(PipelineExecutor::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->andThrow(new \Exception('Pipeline error'));
        });

        $geoSearchService = $this->app->make(GeoSearchService::class);
        $service = new PlumberDispatchService($executor, $workflow, $geoSearchService);

        $customer = User::factory()->create(['role' => 'customer']);
        $serviceType = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
        ]);

        $result = $service->recommendForBooking($booking);

        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEmpty($result['recommendations']);
        $this->assertEquals(0, $result['confidence']);
    }

    /**
     * Test normalize recommendations method.
     */
    public function test_normalize_recommendations(): void
    {
        // 💡 Refactored both instances to container mocks to completely clear static code analyzer warnings
        /** @var PipelineExecutor&MockInterface $executor */
        $executor = $this->mock(PipelineExecutor::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Pipeline test'));
        });

        /** @var DispatchWorkflow&MockInterface $workflow */
        $workflow = $this->mock(DispatchWorkflow::class);

        $geoSearchService = $this->app->make(GeoSearchService::class);
        $service = new PlumberDispatchService($executor, $workflow, $geoSearchService);

        $customer = User::factory()->create(['role' => 'customer']);
        $serviceType = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
        ]);

        $result = $service->recommendForBooking($booking);

        $this->assertEquals('error', $result['status']);
        $this->assertEmpty($result['recommendations']);
        $this->assertEquals(0, $result['confidence']);
    }
}
