<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Services\ContentTypeRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('hook', function ($app) {
            return new \App\Services\HookManager();
        });

        $this->app->singleton(ContentTypeRegistry::class, function () {
            return new ContentTypeRegistry();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }
        });

        // Share active content types with all admin views for dynamic sidebar rendering.
        try {
            if (Schema::hasTable('content_types')) {
                View::share('_contentTypes', app(ContentTypeRegistry::class)->all());
            }
        } catch (\Exception $e) {
            View::share('_contentTypes', collect());
        }
    }
}
