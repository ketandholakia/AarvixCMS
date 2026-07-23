<?php

namespace App\Providers;

use App\Models\Plugin;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PluginServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        try {
            if (Schema::hasTable('plugins')) {
                $activePlugins = Plugin::where('is_active', true)->get();

                foreach ($activePlugins as $plugin) {
                    $manifestPath = base_path("plugins/{$plugin->namespace}/plugin.json");
                    $manifest = file_exists($manifestPath)
                        ? json_decode(file_get_contents($manifestPath), true)
                        : [];

                    $providerClass = $manifest['provider']
                        ?? "\\Plugins\\{$plugin->namespace}\\Providers\\{$plugin->namespace}ServiceProvider";

                    if (! class_exists($providerClass)) {
                        Log::warning("Plugin provider missing: {$providerClass}");
                        continue;
                    }

                    try {
                        $this->app->register($providerClass);
                    } catch (\Throwable $e) {
                        Log::error("Plugin boot failed [{$plugin->namespace}]: {$e->getMessage()}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Plugin bootstrap skipped: ' . $e->getMessage());
        }
    }
}
