<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishThemeAssets extends Command
{
    protected $signature = 'theme:publish {theme?}';

    protected $description = 'Publish assets for themes. If no theme is specified, it publishes assets for all themes.';

    public function handle()
    {
        $theme = $this->argument('theme');
        $themesDir = realpath(base_path('themes')) ?: base_path('themes');
        $publicThemesDir = realpath(public_path('themes')) ?: public_path('themes');
        $failed = false;

        if (!File::isDirectory($publicThemesDir)) {
            File::ensureDirectoryExists($publicThemesDir);
        }

        if ($theme) {
            $failed = $this->publishThemeAssets($theme, $themesDir, $publicThemesDir) !== self::SUCCESS;
        } else {
            $directories = File::directories($themesDir);
            foreach ($directories as $dir) {
                if ($this->publishThemeAssets(basename($dir), $themesDir, $publicThemesDir) !== self::SUCCESS) {
                    $failed = true;
                }
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Theme assets published successfully!');
        return self::SUCCESS;
    }

    protected function publishThemeAssets(string $theme, string $themesDir, string $publicThemesDir): int
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
            $this->error("Invalid theme name: {$theme}");
            return self::FAILURE;
        }

        $sourcePath = $themesDir . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'public';
        $destinationPath = $publicThemesDir . DIRECTORY_SEPARATOR . $theme;

        $resolvedSource = realpath($sourcePath);
        $resolvedDestinationParent = realpath(dirname($destinationPath)) ?: dirname($destinationPath);

        if (! $resolvedSource || ! str_starts_with($resolvedSource, $themesDir . DIRECTORY_SEPARATOR)) {
            $this->error("Theme source path is invalid for {$theme}");
            return self::FAILURE;
        }

        if (! str_starts_with($resolvedDestinationParent, $publicThemesDir)) {
            $this->error("Theme destination path is invalid for {$theme}");
            return self::FAILURE;
        }

        if (File::exists($destinationPath)) {
            if (is_link($destinationPath)) {
                app()->make('files')->delete($destinationPath);
            } else {
                File::deleteDirectory($destinationPath);
            }
        }

        try {
            app()->make('files')->link($resolvedSource, $destinationPath);
            $this->info("Linked assets for theme: {$theme}");
        } catch (\Exception $e) {
            File::copyDirectory($resolvedSource, $destinationPath);
            $this->info("Copied assets for theme: {$theme}");
        }

        return self::SUCCESS;
    }
}
