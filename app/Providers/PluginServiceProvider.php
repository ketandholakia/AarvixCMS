<?php

namespace App\Providers;

use App\Models\Plugin;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class PluginServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Try to load plugins from the database if it exists
        try {
            if (Schema::hasTable('plugins')) {
                $activePlugins = Plugin::where('is_active', true)->get();

                foreach ($activePlugins as $plugin) {
                    $providerClass = "\\Plugins\\{$plugin->namespace}\\Providers\\{$plugin->namespace}ServiceProvider";
                    
                    if (class_exists($providerClass)) {
                        $this->app->register($providerClass);
                    }
                }
            }
        } catch (\Exception $e) {
            // Database not ready, ignore
        }
    }
}
