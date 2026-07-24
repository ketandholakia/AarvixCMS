<?php

namespace App\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\Contracts\VectorStore;
use App\AI\Services\AiManager;
use App\AI\Services\AiAgentRegistryService;
use App\AI\Services\AiAgentExecutionService;
use App\AI\Services\AiPolicyService;
use App\AI\Services\AiToolRegistryService;
use App\AI\Services\ContentEmbeddingService;
use App\AI\Services\ContentEmbeddingSourceResolver;
use App\AI\Services\PromptService;
use App\AI\Services\RetrievalService;
use App\AI\Services\ChatService;
use App\AI\Services\TextEmbeddingService;
use App\AI\Services\VectorStoreBenchmarkService;
use App\AI\Services\UsageService;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class, function ($app) {
            return new AiManager(
                $app,
                config('ai', []),
                $app->make(UsageService::class),
                $app->make(AiPolicyService::class),
                $app->make(\App\Services\SettingService::class),
            );
        });

        $this->app->alias(AiManager::class, 'ai.manager');

        $this->app->bind(AiProvider::class, function ($app) {
            return $app->make(AiManager::class)->provider();
        });

        $this->app->singleton(PromptService::class);
        $this->app->singleton(AiPolicyService::class);
        $this->app->singleton(AiAgentRegistryService::class);
        $this->app->singleton(AiAgentExecutionService::class);
        $this->app->singleton(UsageService::class);
        $this->app->singleton(AiToolRegistryService::class);
        $this->app->singleton(ContentEmbeddingService::class);
        $this->app->singleton(ContentEmbeddingSourceResolver::class);
        $this->app->singleton(TextEmbeddingService::class);
        $this->app->singleton(RetrievalService::class);
        $this->app->singleton(ChatService::class);
        $this->app->singleton(VectorStoreBenchmarkService::class);
        $this->app->singleton(VectorStore::class, function ($app) {
            $driver = config('ai.vector_store.driver', InMemoryVectorStore::class);
            $store = $app->make($driver);

            if (! $store instanceof VectorStore) {
                throw new \RuntimeException("AI vector store driver [{$driver}] must implement " . VectorStore::class . '.');
            }

            return $store;
        });
    }
}
