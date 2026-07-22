<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PublishThemeAssets extends Command
{
    protected $signature = 'theme:publish {theme?}';

    protected $description = 'Publish assets for themes. If no theme is specified, it publishes assets for all themes.';

    public function handle()
    {
        $theme = $this->argument('theme');
        $themesDir = base_path('themes');
        $publicThemesDir = public_path('themes');

        if (!\Illuminate\Support\Facades\File::exists($publicThemesDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($publicThemesDir);
        }

        if ($theme) {
            $this->publishThemeAssets($theme);
        } else {
            $directories = \Illuminate\Support\Facades\File::directories($themesDir);
            foreach ($directories as $dir) {
                $this->publishThemeAssets(basename($dir));
            }
        }

        $this->info('Theme assets published successfully!');
    }

    protected function publishThemeAssets($theme)
    {
        $sourcePath = base_path("themes/{$theme}/public");
        $destinationPath = public_path("themes/{$theme}");

        if (\Illuminate\Support\Facades\File::exists($sourcePath)) {
            if (\Illuminate\Support\Facades\File::exists($destinationPath)) {
                // Remove the old directory or symlink
                if (is_link($destinationPath)) {
                    app()->make('files')->delete($destinationPath);
                } else {
                    \Illuminate\Support\Facades\File::deleteDirectory($destinationPath);
                }
            }

            // Create a symlink or copy
            try {
                app()->make('files')->link($sourcePath, $destinationPath);
                $this->info("Linked assets for theme: {$theme}");
            } catch (\Exception $e) {
                // Fallback to copy if symlink fails
                \Illuminate\Support\Facades\File::copyDirectory($sourcePath, $destinationPath);
                $this->info("Copied assets for theme: {$theme}");
            }
        }
    }
}
