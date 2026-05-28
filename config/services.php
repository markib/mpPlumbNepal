<?php

return [
    'plumbing' => [
        'base_fee' => 350,
        'per_km_fee' => 75,
        'emergency_surcharge' => 500,
        'tax_rate' => 0.0,
    ],
    'local_gateways' => [
        'esewa' => [
            'enabled' => true,
            'callback_url' => env('ESEWA_CALLBACK_URL'),
        ],
        'khalti' => [
            'enabled' => true,
            'callback_url' => env('KHALTI_CALLBACK_URL'),
        ],
        'ime_pay' => [
            'enabled' => true,
            'callback_url' => env('IME_PAY_CALLBACK_URL'),
        ],
    ],
];
