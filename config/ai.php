<?php
// config/ai.php

use App\Providers\CustomOllamaProvider;

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'priority' => [
        'gemini',
        'ollama' 
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Fallback Attempts
    |--------------------------------------------------------------------------
    | Define the order in which providers and models will be tried.
    | This gives you full control over fallback behavior.
    */
    'attempts' => [
        ['provider' => 'gemini', 'model' => 'gemini-2.5-flash'],
        ['provider' => 'gemini', 'model' => 'gemini-2.5-flash-lite-preview'],
        // fallback only if needed
        ['provider' => 'ollama', 'model' => 'gemma2:2b'],
    ],

    'providers' => [
        // Ollama (Local AI)
        'ollama' => [
            'driver'   => 'ollama',
            'key'      => env('OLLAMA_API_KEY', 'ollama'),   // dummy key required by SDK
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'models'    => [
                'text' => [
                    'default' => 'qwen2.5-coder:1.5b',
                    'cheapest' => 'gemma2:2b',
                ],
            ],
            'timeout'  => env('OLLAMA_TIMEOUT', 180),        // longer timeout for local
        ],

        'gemini' => [
            'driver'   => 'gemini',
            'key'      => env('GEMINI_API_KEY'),
            'api_key'  => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model'    => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'timeout'  => env('GEMINI_TIMEOUT', 60),
        ],

        // 'nvidia' => [
        //     'driver'   => 'nvidia',
        //     'key'      => env('NVIDIA_API_KEY'),
        //     'api_key'  => env('NVIDIA_API_KEY'),
        //     'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        //     'model'    => env('NVIDIA_MODEL', 'mistralai/mistral-medium-3.5-128b'),
        //     'timeout'  => env('NVIDIA_TIMEOUT', 60),
        // ],
    ]
];
