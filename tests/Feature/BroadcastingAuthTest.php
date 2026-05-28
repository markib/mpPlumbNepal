<?php

namespace Tests\Feature;

use App\Models\PlumberProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastingAuthTest extends TestCase
{
    public function test_plumber_can_authorize_own_private_channel(): void
    {
        $plumber = User::factory()->plumber()->create();
        $profile = PlumberProfile::factory()->for($plumber, 'user')->create();

        Sanctum::actingAs($plumber);

        $response = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-plumbers.'.$profile->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_plumber_can_authorize_own_private_channel_with_form_encoded_payload(): void
    {
        $plumber = User::factory()->plumber()->create();
        $profile = PlumberProfile::factory()->for($plumber, 'user')->create();

        Sanctum::actingAs($plumber);

        $response = $this->post('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-plumbers.'.$profile->id,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_customer_can_authorize_own_user_channel(): void
    {
        $customer = User::factory()->customer()->create();

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-user.'.$customer->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['auth']);
    }
}
