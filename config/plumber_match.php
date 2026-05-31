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

    'dispatch_agent' => [
        'enabled' => true,
        'weights' => [
            'rating' => 0.30,
            'distance' => 0.25,
            'work_history' => 0.25,
            'skill_match' => 0.15,
            'availability' => 0.05,
        ],
        'confidence_threshold' => 0.6,
        'max_recommendations' => 10,
        'min_recommendations' => 3,
        'search_radius_km' => 15,
        'min_rating_for_recommendation' => 3.5,
    ],
];
