<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'customer@plumbnepal.test'],
            [
                'name' => 'Test Customer',
                'phone' => '9800000001',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@plumbnepal.test'],
            [
                'name' => 'Main Admin',
                'phone' => '9800000002',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'locale' => 'en',
            ]
        );

        User::firstOrCreate(
            ['email' => 'service@plumbnepal.test'],
            [
                'name' => 'Service Provider',
                'phone' => '9800000003',
                'password' => Hash::make('service1234'),
                'role' => 'service_provider',
                'locale' => 'en',
            ]
        );

        User::firstOrCreate(
            ['email' => 'plumber@plumbnepal.test'],
            [
                'name' => 'Verified Plumber',
                'phone' => '9800000004',
                'password' => Hash::make('plumber1234'),
                'role' => 'plumber',
                'locale' => 'en',
            ]
        );

        User::firstOrCreate(
            ['email' => 'shop@plumbnepal.test'],
            [
                'name' => 'Shop Keeper',
                'phone' => '9800000005',
                'password' => Hash::make('shop1234'),
                'role' => 'shop_keeper',
                'locale' => 'en',
            ]
        );
    }
}
