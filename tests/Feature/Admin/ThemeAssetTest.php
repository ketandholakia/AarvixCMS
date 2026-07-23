<?php

namespace Tests\Feature\Admin;

use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_asset_url_uses_manifest_cdn_when_present(): void
    {
        $themePath = base_path('themes/test-cdn');
        File::ensureDirectoryExists($themePath);
        File::put($themePath . '/theme.json', json_encode([
            'name' => 'Test CDN',
            'asset_base_url' => 'https://cdn.example.com/themes/test-cdn',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            $manager = app(ThemeManager::class);

            $this->assertSame(
                'https://cdn.example.com/themes/test-cdn/style.css',
                $manager->themeAssetUrl('test-cdn', 'style.css')
            );

            $this->assertSame(
                asset('themes/default/style.css'),
                $manager->themeAssetUrl('default', 'style.css')
            );
        } finally {
            File::delete($themePath . '/theme.json');
            File::deleteDirectory($themePath);
        }
    }

    public function test_theme_assets_are_loaded_from_manifest(): void
    {
        $themePath = base_path('themes/test-assets');
        File::ensureDirectoryExists($themePath);
        File::put($themePath . '/theme.json', json_encode([
            'name' => 'Test Assets',
            'assets' => [
                'styles' => ['public/style.css', 'https://cdn.example.com/theme.css'],
                'scripts' => ['public/app.js'],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            $manager = app(ThemeManager::class);

            $this->assertSame([
                asset('themes/test-assets/public/style.css'),
                'https://cdn.example.com/theme.css',
            ], $manager->themeStyles('test-assets'));

            $this->assertSame([
                asset('themes/test-assets/public/app.js'),
            ], $manager->themeScripts('test-assets'));
        } finally {
            File::delete($themePath . '/theme.json');
            File::deleteDirectory($themePath);
        }
    }
}
