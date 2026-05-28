<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingProposal;
use App\Models\PlumberProfile;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleServiceDataSeeder extends Seeder
{
    public function run(): void
    {
        $locationData = function (float $latitude, float $longitude, string $column = 'location'): array {
            if (DB::getDriverName() === 'pgsql') {
                return [$column => DB::raw("ST_GeogFromText('SRID=4326;POINT({$longitude} {$latitude})')")];
            }

            if ($column === 'pickup_location') {
                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'pickup_latitude' => $latitude,
                    'pickup_longitude' => $longitude,
                ];
            }

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        };

        $serviceType = ServiceType::firstOrCreate(
            ['name' => 'General Plumbing'],
            [
                'description' => 'Standard plumbing services for household repairs and installations.',
                'fee' => 500,
                'is_emergency_available' => true,
            ]
        );

        $drainCleaningType = ServiceType::firstOrCreate(
            ['name' => 'Drain Cleaning'],
            [
                'description' => 'Drain cleaning and unclogging service for sinks, showers, and drains.',
                'fee' => 700,
                'is_emergency_available' => true,
            ]
        );

        $leakRepairType = ServiceType::firstOrCreate(
            ['name' => 'Leak Repair'],
            [
                'description' => 'Emergency and scheduled pipe, tap, and fixture leak repairs.',
                'fee' => 900,
                'is_emergency_available' => true,
            ]
        );

        $waterHeaterType = ServiceType::firstOrCreate(
            ['name' => 'Water Heater'],
            [
                'description' => 'Geyser and water heater diagnosis, repair, and installation.',
                'fee' => 1400,
                'is_emergency_available' => false,
            ]
        );

        $bathroomInstallType = ServiceType::firstOrCreate(
            ['name' => 'Bathroom Installation'],
            [
                'description' => 'Bathroom fixture installation, pipe fitting, and finishing support.',
                'fee' => 2000,
                'is_emergency_available' => false,
            ]
        );

        $customerOne = User::firstOrCreate(
            ['email' => 'customer@plumbnepal.test'],
            [
                'name' => 'Test Customer',
                'phone' => '9800000001',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $customerTwo = User::firstOrCreate(
            ['email' => 'customer2@plumbnepal.test'],
            [
                'name' => 'Second Customer',
                'phone' => '9800000006',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $customerThree = User::firstOrCreate(
            ['email' => 'customer3@plumbnepal.test'],
            [
                'name' => 'Patan Customer',
                'phone' => '9800000010',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $customerFour = User::firstOrCreate(
            ['email' => 'customer4@plumbnepal.test'],
            [
                'name' => 'Bhaktapur Customer',
                'phone' => '9800000011',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $plumberOne = User::firstOrCreate(
            ['email' => 'plumber@plumbnepal.test'],
            [
                'name' => 'Verified Plumber',
                'phone' => '9800000004',
                'password' => bcrypt('plumber1234'),
                'role' => 'plumber',
                'locale' => 'en',
            ]
        );
        $plumberOne->forceFill([
            'citizenship_verified' => true,
            'verification_status' => 'approved',
            'verified_badge' => true,
        ])->save();

        $plumberTwo = User::firstOrCreate(
            ['email' => 'plumber2@plumbnepal.test'],
            [
                'name' => 'Second Plumber',
                'phone' => '9800000007',
                'password' => bcrypt('plumber1234'),
                'role' => 'plumber',
                'locale' => 'en',
            ]
        );
        $plumberTwo->forceFill([
            'citizenship_verified' => true,
            'verification_status' => 'approved',
            'verified_badge' => true,
        ])->save();

        $profileOne = PlumberProfile::updateOrCreate(
            ['user_id' => $plumberOne->id],
            array_merge([
                'service_type_ids' => [$serviceType->id, $leakRepairType->id],
                'is_available' => true,
                'is_online' => true,
                'available_since' => now()->subHours(2),
                'verified' => true,
                'rating' => 4.9,
            ], $locationData(27.7172, 85.3240))
        );

        $profileTwo = PlumberProfile::updateOrCreate(
            ['user_id' => $plumberTwo->id],
            array_merge([
                'service_type_ids' => [$serviceType->id, $drainCleaningType->id],
                'is_available' => true,
                'is_online' => true,
                'available_since' => now()->subHours(1),
                'verified' => true,
                'rating' => 4.7,
            ], $locationData(27.7150, 85.3335))
        );

        $extraPlumbers = [
            [
                'email' => 'leak.patan@plumbnepal.test',
                'name' => 'Patan Leak Specialist',
                'phone' => '9800000012',
                'rating' => 4.8,
                'latitude' => 27.6766,
                'longitude' => 85.3188,
                'service_type_ids' => [$leakRepairType->id, $serviceType->id],
            ],
            [
                'email' => 'drain.boudha@plumbnepal.test',
                'name' => 'Boudha Drain Expert',
                'phone' => '9800000013',
                'rating' => 4.6,
                'latitude' => 27.7218,
                'longitude' => 85.3612,
                'service_type_ids' => [$drainCleaningType->id, $serviceType->id],
            ],
            [
                'email' => 'heater.kalanki@plumbnepal.test',
                'name' => 'Kalanki Water Heater Pro',
                'phone' => '9800000014',
                'rating' => 4.5,
                'latitude' => 27.6935,
                'longitude' => 85.2805,
                'service_type_ids' => [$waterHeaterType->id, $serviceType->id],
            ],
            [
                'email' => 'bathroom.bhaktapur@plumbnepal.test',
                'name' => 'Bhaktapur Bathroom Fitter',
                'phone' => '9800000015',
                'rating' => 4.4,
                'latitude' => 27.6710,
                'longitude' => 85.4298,
                'service_type_ids' => [$bathroomInstallType->id, $leakRepairType->id],
            ],
            [
                'email' => 'multi.lalitpur@plumbnepal.test',
                'name' => 'Lalitpur Multi Skill Plumber',
                'phone' => '9800000016',
                'rating' => 4.95,
                'latitude' => 27.6862,
                'longitude' => 85.3169,
                'service_type_ids' => [$serviceType->id, $drainCleaningType->id, $leakRepairType->id, $waterHeaterType->id],
            ],
        ];

        foreach ($extraPlumbers as $plumberData) {
            $plumber = User::firstOrCreate(
                ['email' => $plumberData['email']],
                [
                    'name' => $plumberData['name'],
                    'phone' => $plumberData['phone'],
                    'password' => bcrypt('plumber1234'),
                    'role' => 'plumber',
                    'locale' => 'en',
                ]
            );

            $plumber->forceFill([
                'name' => $plumberData['name'],
                'phone' => $plumberData['phone'],
                'role' => 'plumber',
                'citizenship_verified' => true,
                'verification_status' => 'approved',
                'verified_badge' => true,
            ])->save();

            PlumberProfile::updateOrCreate(
                ['user_id' => $plumber->id],
                array_merge([
                    'service_type_ids' => $plumberData['service_type_ids'],
                    'is_available' => true,
                    'is_online' => true,
                    'available_since' => now()->subMinutes(rand(20, 180)),
                    'verified' => true,
                    'rating' => $plumberData['rating'],
                ], $locationData($plumberData['latitude'], $plumberData['longitude']))
            );
        }

        $bookingOne = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Thamel',
                'ward_number' => '01',
                'tole_name' => 'Old Bazaar',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'proposed',
                'payment_method' => 'cod',
                'amount' => 1200,
                'is_emergency' => false,
                'service_notes' => 'Kitchen sink leak and low water pressure.',
            ] + $locationData(27.7172, 85.3240, 'pickup_location')
        );

        BookingProposal::updateOrCreate(
            [
                'booking_id' => $bookingOne->id,
                'plumber_profile_id' => $profileOne->id,
            ],
            [
                'base_fee' => 850,
                'material_cost' => 150,
                'eta_minutes' => 45,
                'proposal_terms' => ['notes' => 'Can repair same day with spare parts included.'],
                'status' => 'proposed',
            ]
        );

        BookingProposal::updateOrCreate(
            [
                'booking_id' => $bookingOne->id,
                'plumber_profile_id' => $profileTwo->id,
            ],
            [
                'base_fee' => 800,
                'material_cost' => 180,
                'eta_minutes' => 40,
                'proposal_terms' => ['notes' => 'Available immediately and can inspect for hidden leaks.'],
                'status' => 'proposed',
            ]
        );

        $bookingTwo = Booking::updateOrCreate(
            [
                'user_id' => $customerTwo->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Boudha',
                'ward_number' => '05',
                'tole_name' => 'Boudha Marg',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'esewa',
                'amount' => 950,
                'is_emergency' => false,
                'service_notes' => 'Toilet flush not working properly.',
            ] + $locationData(27.7211, 85.3604, 'pickup_location')
        );

        $bookingOpenOne = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Kathmandu Guest House',
                'ward_number' => '01',
                'tole_name' => 'Thamel',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 1100,
                'is_emergency' => false,
                'service_notes' => 'Hot water line leaking in the bathroom.',
            ] + $locationData(27.7165, 85.3255, 'pickup_location')
        );

        $bookingOpenTwo = Booking::updateOrCreate(
            [
                'user_id' => $customerTwo->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Lazimpat',
                'ward_number' => '13',
                'tole_name' => 'Shangri-La Area',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 980,
                'is_emergency' => false,
                'service_notes' => 'Low pressure in bathroom tap and noisy pump.',
            ] + $locationData(27.7155, 85.3330, 'pickup_location')
        );

        $bookingOpenThree = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $drainCleaningType->id,
                'landmark' => 'New Road',
                'ward_number' => '11',
                'tole_name' => 'New Road Street',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 880,
                'is_emergency' => false,
                'service_notes' => 'Drain in kitchen sink keeps clogging when washing dishes.',
            ] + $locationData(27.7138, 85.3203, 'pickup_location')
        );

        $sampleOpenRequests = [
            [
                'customer' => $customerThree,
                'service_type' => $leakRepairType,
                'landmark' => 'Patan Durbar Square',
                'ward_number' => '16',
                'tole_name' => 'Mangal Bazaar',
                'payment_method' => 'cod',
                'amount' => 1250,
                'is_emergency' => true,
                'service_notes' => 'Pipe under the kitchen sink is leaking continuously.',
                'latitude' => 27.6766,
                'longitude' => 85.3240,
            ],
            [
                'customer' => $customerTwo,
                'service_type' => $drainCleaningType,
                'landmark' => 'Boudha Stupa Gate',
                'ward_number' => '06',
                'tole_name' => 'Boudha',
                'payment_method' => 'esewa',
                'amount' => 950,
                'is_emergency' => false,
                'service_notes' => 'Bathroom drain is clogged and water is backing up.',
                'latitude' => 27.7215,
                'longitude' => 85.3620,
            ],
            [
                'customer' => $customerOne,
                'service_type' => $waterHeaterType,
                'landmark' => 'Kalanki Chowk',
                'ward_number' => '14',
                'tole_name' => 'Kalanki',
                'payment_method' => 'khalti',
                'amount' => 1600,
                'is_emergency' => false,
                'service_notes' => 'Electric geyser is not heating water.',
                'latitude' => 27.6940,
                'longitude' => 85.2810,
            ],
            [
                'customer' => $customerFour,
                'service_type' => $bathroomInstallType,
                'landmark' => 'Bhaktapur Durbar Square',
                'ward_number' => '01',
                'tole_name' => 'Taumadhi',
                'payment_method' => 'cod',
                'amount' => 2300,
                'is_emergency' => false,
                'service_notes' => 'Install commode, basin mixer, and shower fittings.',
                'latitude' => 27.6720,
                'longitude' => 85.4298,
            ],
            [
                'customer' => $customerThree,
                'service_type' => $serviceType,
                'landmark' => 'Jawalakhel Zoo',
                'ward_number' => '04',
                'tole_name' => 'Jawalakhel',
                'payment_method' => 'ime_pay',
                'amount' => 1050,
                'is_emergency' => false,
                'service_notes' => 'Bathroom tap has low pressure and needs inspection.',
                'latitude' => 27.6726,
                'longitude' => 85.3128,
            ],
        ];

        foreach ($sampleOpenRequests as $requestData) {
            Booking::updateOrCreate(
                [
                    'user_id' => $requestData['customer']->id,
                    'service_type_id' => $requestData['service_type']->id,
                    'landmark' => $requestData['landmark'],
                    'ward_number' => $requestData['ward_number'],
                    'tole_name' => $requestData['tole_name'],
                ],
                [
                    'status_id' => 1,
                    'workflow_status' => 'pending',
                    'payment_method' => $requestData['payment_method'],
                    'amount' => $requestData['amount'],
                    'is_emergency' => $requestData['is_emergency'],
                    'service_notes' => $requestData['service_notes'],
                    'accepted_by_id' => null,
                    'contract_terms' => null,
                    'contract_start_code' => null,
                    'contracted_at' => null,
                    'job_order_json' => null,
                    'job_started_at' => null,
                ] + $locationData($requestData['latitude'], $requestData['longitude'], 'pickup_location')
            );
        }

        $bookingThree = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'New Baneshwor',
                'ward_number' => '09',
                'tole_name' => 'Bakhundol',
            ],
            [
                'status_id' => 2,
                'workflow_status' => 'contracted',
                'payment_method' => 'khalti',
                'amount' => 1500,
                'is_emergency' => true,
                'service_notes' => 'Burst pipe repair in bathroom.',
                'plumber_profile_id' => $profileOne->id,
                'accepted_by_id' => $profileOne->id,
                'contract_terms' => [
                    'base_fee' => 1200,
                    'material_cost' => 300,
                    'eta_minutes' => 60,
                    'details' => ['repair' => 'pipe replacement', 'warranty' => '7 days'],
                ],
                'contract_start_code' => '7421',
                'contracted_at' => now()->subHours(3),
                'job_order_json' => [
                    'booking_id' => null,
                    'customer_id' => $customerOne->id,
                    'plumber_profile_id' => $profileOne->id,
                    'contract_terms' => [
                        'base_fee' => 1200,
                        'material_cost' => 300,
                        'eta_minutes' => 60,
                    ],
                    'created_at' => now()->toIso8601String(),
                ],
            ] + $locationData(27.7114, 85.3296, 'pickup_location')
        );

        if (is_array($bookingThree->job_order_json)) {
            $jobOrderJson = $bookingThree->job_order_json;
            $jobOrderJson['booking_id'] = $bookingThree->id;
            $bookingThree->job_order_json = $jobOrderJson;
            $bookingThree->save();
        }
    }
}
