<?php

namespace App\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\Services\AiManager;
use App\AI\Services\PromptService;
use App\AI\Services\UsageService;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class, function ($app) {
            return new AiManager($app, config('ai', []), $app->make(UsageService::class));
        });

        $this->app->alias(AiManager::class, 'ai.manager');

        $this->app->bind(AiProvider::class, function ($app) {
            return $app->make(AiManager::class)->provider();
        });

        $this->app->singleton(PromptService::class);
        $this->app->singleton(UsageService::class);
    }
}
