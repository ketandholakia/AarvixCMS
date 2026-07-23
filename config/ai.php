<?php

use App\AI\Enums\AiCapability;
use App\AI\Providers\FakeAiProvider;
use App\AI\Providers\OpenAiProvider;
use App\AI\Support\VectorStores\InMemoryVectorStore;

return [
    'enabled' => env('AI_ENABLED', false),
    'timeout' => (int) env('AI_TIMEOUT', 60),

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'fake'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'fake'),

    'retry' => [
        'attempts' => (int) env('AI_RETRY_ATTEMPTS', 2),
        'delay_ms' => (int) env('AI_RETRY_DELAY_MS', 250),
        'retryable_status_codes' => [408, 425, 429, 500, 502, 503, 504],
    ],

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
                'public_generation_enabled' => false,
                'moderation_required' => true,
                'retention_days' => (int) env('AI_IMAGE_RETENTION_DAYS', 30),
                'supports_edit' => true,
                'supports_mask' => true,
                'supports_seed' => true,
                'supports_resolution' => true,
            ],
        ],
        'openai' => [
            'driver' => OpenAiProvider::class,
            'api_key' => env('AI_OPENAI_API_KEY'),
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'organization' => env('AI_OPENAI_ORGANIZATION'),
            'timeout' => (int) env('AI_OPENAI_TIMEOUT', env('AI_TIMEOUT', 60)),
            'retries' => (int) env('AI_OPENAI_RETRIES', env('AI_RETRY_ATTEMPTS', 2)),
            'capabilities' => [
                AiCapability::Generate->value,
                AiCapability::Stream->value,
                AiCapability::Chat->value,
                AiCapability::Embedding->value,
                AiCapability::Json->value,
            ],
            'models' => [
                'generate' => env('AI_OPENAI_CHAT_MODEL', 'gpt-4.1-mini'),
                'chat' => env('AI_OPENAI_CHAT_MODEL', 'gpt-4.1-mini'),
                'json' => env('AI_OPENAI_CHAT_MODEL', 'gpt-4.1-mini'),
                'embedding' => env('AI_OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
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

    'embeddings' => [
        'chunker_version' => env('AI_EMBEDDING_CHUNKER_VERSION', '1'),
        'model' => env('AI_EMBEDDING_MODEL', env('AI_OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small')),
    ],

    'vector_store' => [
        'driver' => env('AI_VECTOR_STORE_DRIVER', InMemoryVectorStore::class),
        'collection' => env('AI_VECTOR_STORE_COLLECTION', 'content_embeddings'),
    ],
];
