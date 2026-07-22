<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use App\Models\Setting;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('theme.manager', function ($app) {
            return new \App\Services\ThemeManager();
        });
    }

    public function boot()
    {
        $activeTheme = 'default';
        
        try {
            if (Schema::hasTable('settings')) {
                $activeTheme = app('theme.manager')->getActiveTheme();
            }
        } catch (\Exception $e) {
            // Database not ready, stick with default
        }
        
        // Only override frontend views, let admin views stay in resources/views/admin
        $themeViewsPath = base_path("themes/{$activeTheme}/views");
        
        if (is_dir($themeViewsPath)) {
            // Prepend the theme location so it takes precedence over resources/views
            View::prependLocation($themeViewsPath);
        }
        // Register the @themeAsset Blade directive
        Blade::directive('themeAsset', function ($expression) {
            return "<?php echo asset('themes/' . app('theme.manager')->getActiveTheme() . '/' . trim($expression, \"'\")); ?>";
        });
    }
}
