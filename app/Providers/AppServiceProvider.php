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
        View::composer('layouts.admin', function ($view) {
            try {
                if (Schema::hasTable('content_types')) {
                    $view->with('_contentTypes', app(ContentTypeRegistry::class)->all());
                } else {
                    $view->with('_contentTypes', collect());
                }
            } catch (\Exception $e) {
                $view->with('_contentTypes', collect());
            }
        });
    }
}
