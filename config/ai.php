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

    'agents' => [
        'seo' => [
            'version' => 1,
            'name' => 'SEO Agent',
            'description' => 'Produces search-friendly content suggestions and metadata proposals.',
            'prompt' => 'ai.agents.seo.v1',
            'tools' => ['content.summary', 'seo.propose'],
            'memory' => [
                'store' => 'session',
                'retention' => 'single-turn',
            ],
            'permissions' => ['use_ai_writer'],
            'model_policy' => [
                'primary' => 'writer',
                'fallback' => 'chat',
                'temperature' => 0.4,
            ],
            'budgets' => [
                'max_tokens' => 1800,
                'max_cost' => '0.50',
            ],
            'max_steps' => 3,
            'is_enabled' => true,
        ],
        'marketing' => [
            'version' => 1,
            'name' => 'Marketing Agent',
            'description' => 'Drafts campaign copy and social variants from approved content.',
            'prompt' => 'ai.agents.marketing.v1',
            'tools' => ['content.summary', 'content.draft', 'media.search'],
            'memory' => [
                'store' => 'session',
                'retention' => 'multi-turn',
            ],
            'permissions' => ['use_ai_writer', 'view_media'],
            'model_policy' => [
                'primary' => 'writer',
                'fallback' => 'chat',
                'temperature' => 0.65,
            ],
            'budgets' => [
                'max_tokens' => 2400,
                'max_cost' => '1.00',
            ],
            'max_steps' => 4,
            'is_enabled' => true,
        ],
        'translation' => [
            'version' => 1,
            'name' => 'Translation Agent',
            'description' => 'Translates CMS content while preserving structure and links.',
            'prompt' => 'ai.agents.translation.v1',
            'tools' => ['content.summary'],
            'memory' => [
                'store' => 'none',
                'retention' => 'single-turn',
            ],
            'permissions' => ['use_ai_writer'],
            'model_policy' => [
                'primary' => 'writer',
                'fallback' => 'chat',
                'temperature' => 0.2,
            ],
            'budgets' => [
                'max_tokens' => 2200,
                'max_cost' => '0.80',
            ],
            'max_steps' => 3,
            'is_enabled' => true,
        ],
        'documentation' => [
            'version' => 1,
            'name' => 'Documentation Agent',
            'description' => 'Turns internal knowledge into admin-facing documentation drafts.',
            'prompt' => 'ai.agents.documentation.v1',
            'tools' => ['content.search', 'content.summary', 'ai.report'],
            'memory' => [
                'store' => 'session',
                'retention' => 'multi-turn',
            ],
            'permissions' => ['view_ai_usage'],
            'model_policy' => [
                'primary' => 'chat',
                'fallback' => 'writer',
                'temperature' => 0.3,
            ],
            'budgets' => [
                'max_tokens' => 2000,
                'max_cost' => '0.75',
            ],
            'max_steps' => 4,
            'is_enabled' => true,
        ],
        'support' => [
            'version' => 1,
            'name' => 'Support Agent',
            'description' => 'Answers CMS support requests using only approved read tools.',
            'prompt' => 'ai.agents.support.v1',
            'tools' => ['content.search', 'content.summary', 'media.search'],
            'memory' => [
                'store' => 'conversation',
                'retention' => 'rolling',
            ],
            'permissions' => ['view_ai_usage'],
            'model_policy' => [
                'primary' => 'chat',
                'fallback' => 'writer',
                'temperature' => 0.15,
            ],
            'budgets' => [
                'max_tokens' => 1600,
                'max_cost' => '0.40',
            ],
            'max_steps' => 2,
            'is_enabled' => true,
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
