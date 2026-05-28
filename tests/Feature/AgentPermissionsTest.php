<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PlumberProfile;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentPermissionsTest extends TestCase
{
    public function test_authenticated_ai_diagnosis_remains_accessible_to_booking_owner_via_booking_show(): void
    {
        $customer = User::factory()->customer()->create();
        $serviceType = ServiceType::factory()->create();
        $diagnosis = \App\Models\AiDiagnosis::factory()->create(['user_id' => $customer->id]);
        $booking = Booking::factory()->for($customer)->for($serviceType)->create([
            'ai_diagnosis_id' => $diagnosis->id,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('ai_diagnosis_id', $diagnosis->id);
    }

    public function test_guest_cannot_access_authenticated_agent_connected_routes(): void
    {
        $booking = Booking::factory()->create();

        $this->getJson('/api/v1/customer/proposals')->assertUnauthorized();
        $this->getJson('/api/v1/plumber/open-requests')->assertUnauthorized();
        $this->postJson("/api/v1/bookings/{$booking->id}/proposals", [])->assertUnauthorized();
        $this->postJson("/api/v1/bookings/{$booking->id}/start-job", [])->assertUnauthorized();
    }

    public function test_non_plumber_cannot_submit_proposal(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/proposals", [
            'base_fee' => 1000,
            'material_cost' => 100,
            'eta_minutes' => 20,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_unverified_plumber_cannot_submit_proposal(): void
    {
        $serviceType = ServiceType::factory()->create();
        $plumber = User::factory()->plumber(citizenshipVerified: false)->create();
        PlumberProfile::factory()->for($plumber, 'user')->create([
            'service_type_ids' => [$serviceType->id],
            'verified' => true,
        ]);
        $booking = Booking::factory()->for($serviceType)->create([
            'workflow_status' => 'pending',
        ]);

        Sanctum::actingAs($plumber);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/proposals", [
            'base_fee' => 1000,
            'material_cost' => 100,
            'eta_minutes' => 20,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Plumber not eligible to send proposals');

        $this->assertDatabaseMissing('booking_proposals', [
            'booking_id' => $booking->id,
        ]);
    }

    public function test_verified_plumber_can_submit_matching_proposal_and_updates_booking_state(): void
    {
        Event::fake();
        $serviceType = ServiceType::factory()->create();
        $plumber = User::factory()->plumber()->create();
        $profile = PlumberProfile::factory()->for($plumber, 'user')->create([
            'service_type_ids' => [$serviceType->id],
            'verified' => true,
            'is_available' => true,
            'is_online' => true,
        ]);
        $booking = Booking::factory()->for($serviceType)->create([
            'workflow_status' => 'pending',
        ]);

        Sanctum::actingAs($plumber);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/proposals", [
            'base_fee' => 1000,
            'material_cost' => 100,
            'eta_minutes' => 20,
            'proposal_terms' => ['warranty_days' => 14],
        ]);

        $response->assertCreated()
            ->assertJsonPath('proposal.plumber_profile_id', $profile->id);

        $this->assertDatabaseHas('booking_proposals', [
            'booking_id' => $booking->id,
            'plumber_profile_id' => $profile->id,
            'status' => 'proposed',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'workflow_status' => 'proposed',
        ]);
    }
}
