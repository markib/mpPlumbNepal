<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'name' => "Test User {$sequence}",
            'email' => "user{$sequence}@example.com",
            'phone' => '98'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'customer',
            'locale' => 'en',
            'verification_status' => 'unverified',
            'verified_badge' => false,
            'citizenship_verified' => false,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn () => [
            'role' => 'customer',
            'citizenship_verified' => false,
        ]);
    }

    public function plumber(bool $citizenshipVerified = true): static
    {
        return $this->state(fn () => [
            'role' => 'plumber',
            'citizenship_verified' => $citizenshipVerified,
        ]);
    }
}
