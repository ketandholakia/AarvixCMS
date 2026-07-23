<?php

use App\AI\Enums\AiCapability;
use App\AI\Providers\FakeAiProvider;

return [
    'enabled' => env('AI_ENABLED', false),

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'fake'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'fake'),

    'providers' => [
        'fake' => [
            'driver' => FakeAiProvider::class,
            'capabilities' => [
                AiCapability::Generate->value,
                AiCapability::Stream->value,
                AiCapability::Chat->value,
                AiCapability::Embedding->value,
                AiCapability::Image->value,
                AiCapability::Vision->value,
                AiCapability::Json->value,
            ],
            'image' => [
                'supports_edit' => true,
                'supports_mask' => true,
                'supports_seed' => true,
                'supports_resolution' => true,
            ],
        ],
    ],

    'models' => [
        'writer' => [
            'provider' => 'fake',
            'model' => 'fake-writer',
        ],
        'chat' => [
            'provider' => 'fake',
            'model' => 'fake-chat',
        ],
    ],

    'queue' => [
        'high' => env('AI_QUEUE_HIGH', 'ai-high'),
        'medium' => env('AI_QUEUE_MEDIUM', 'ai-medium'),
        'low' => env('AI_QUEUE_LOW', 'ai-low'),
    ],

    'limits' => [
        'requests_per_minute' => (int) env('AI_REQUESTS_PER_MINUTE', 30),
        'daily_token_cap' => (int) env('AI_DAILY_TOKEN_CAP', 100000),
        'daily_cost_cap' => env('AI_DAILY_COST_CAP', '25.00'),
        'monthly_cost_cap' => env('AI_MONTHLY_COST_CAP', '250.00'),
    ],

    'logging' => [
        'log_prompts' => env('AI_LOG_PROMPTS', false),
        'log_responses' => env('AI_LOG_RESPONSES', false),
        'retention_days' => (int) env('AI_LOG_RETENTION_DAYS', 30),
    ],
];
