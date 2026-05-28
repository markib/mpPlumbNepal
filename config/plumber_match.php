<?php

return [
    'search_radius_km' => (int) env('PLUMBER_SEARCH_RADIUS', 15),

    'min_rating' => (float) env('PLUMBER_MIN_RATING', 3.5),

    'broadcast_timeout_seconds' => (int) env('BROADCAST_TIMEOUT', 120),

    'max_broadcast_recipients' => (int) env('MAX_BROADCAST_RECIPIENTS', 20),

    'average_speed_kmh' => 30,

    'weights' => [
        'distance' => 0.4,
        'rating' => 0.3,
        'response_time' => 0.2,
        'skill_match' => 0.1,
    ],

    'lock_timeout_seconds' => 10,

    'broadcast_retry_attempts' => 3,

    'broadcast_retry_delay_seconds' => 30,
];