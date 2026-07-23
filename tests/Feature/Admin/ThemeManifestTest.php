<?php

namespace Tests\Feature\Admin;

use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_with_missing_parent_is_rejected(): void
    {
        $themePath = base_path('themes/test-bad-parent');
        File::ensureDirectoryExists($themePath . '/views');
        File::put($themePath . '/theme.json', json_encode([
            'name' => 'Bad Parent',
            'parent' => 'missing-parent',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("references missing parent theme");

            app(ThemeManager::class)->getAllThemes();
        } finally {
            File::delete($themePath . '/theme.json');
            File::deleteDirectory($themePath);
        }
    }
}
