<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('theme.manager', function ($app) {
            return new \App\Services\ThemeManager();
        });

        $this->app->singleton('theme.settings', function ($app) {
            return new \App\Services\ThemeSettingsService();
        });
    }

    public function boot()
    {
        $activeTheme = 'default';
        $themeManager = app('theme.manager');
        
        try {
            if (Schema::hasTable('settings')) {
                $activeTheme = $themeManager->getActiveTheme();
            }
        } catch (\Exception $e) {
            // Database not ready, stick with default
        }

        $themePaths = [];
        foreach ($themeManager->getThemeChain($activeTheme) as $themeName) {
            $path = base_path("themes/{$themeName}/views");
            if (is_dir($path)) {
                $themePaths[] = $path;
            }
        }

        if (! empty($themePaths)) {
            View::addNamespace('theme', $themePaths);
        }

        // Register the @themeAsset Blade directive
        Blade::directive('themeAsset', function ($expression) {
            return "<?php echo app('theme.manager')->themeAssetUrl(app('theme.manager')->getActiveTheme(), trim({$expression}, \"'\\\"\")); ?>";
        });

        Blade::directive('themeStyles', function () {
            return "<?php foreach (app('theme.manager')->themeStyles(app('theme.manager')->getActiveTheme()) as \$themeStyle): ?><link rel=\"stylesheet\" href=\"<?php echo e(\$themeStyle); ?>\"><?php endforeach; ?>";
        });

        Blade::directive('themeScripts', function () {
            return "<?php foreach (app('theme.manager')->themeScripts(app('theme.manager')->getActiveTheme()) as \$themeScript): ?><script src=\"<?php echo e(\$themeScript); ?>\" defer></script><?php endforeach; ?>";
        });

        Blade::directive('themePart', function ($expression) {
            return "<?php echo view()->first(app('theme.manager')->themePartPath(trim({$expression}, \"'\\\"\")))->render(); ?>";
        });

        Blade::directive('themeSection', function ($expression) {
            return "<?php \$__themeSection = trim({$expression}, \"'\\\"\"); \$__themeContent = app('theme.manager')->getThemeSectionContent(app('theme.manager')->getActiveTheme(), \$__themeSection); echo \$__themeContent !== null && \$__themeContent !== '' ? \$__themeContent : view()->first(app('theme.manager')->themeSectionPath(\$__themeSection))->render(); ?>";
        });
    }
}
