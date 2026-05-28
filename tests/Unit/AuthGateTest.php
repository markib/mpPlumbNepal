<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthGateTest extends TestCase
{
    public function test_plumber_gate_before_allows_booking_update_and_accept_actions(): void
    {
        $plumber = User::factory()->plumber()->create();

        $this->assertTrue(Gate::forUser($plumber)->allows('update-booking'));
        $this->assertTrue(Gate::forUser($plumber)->allows('accept-booking'));
    }

    public function test_customer_gate_before_does_not_grant_plumber_only_actions(): void
    {
        $customer = User::factory()->customer()->create();

        $this->assertFalse(Gate::forUser($customer)->allows('update-booking'));
        $this->assertFalse(Gate::forUser($customer)->allows('accept-booking'));
    }
}
