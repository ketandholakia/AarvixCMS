<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ThemeInheritanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_theme_overrides_parent_views(): void
    {
        $childThemePath = base_path('themes/test-child');
        File::ensureDirectoryExists($childThemePath . '/views/partials');
        File::ensureDirectoryExists($childThemePath . '/public');

        File::put($childThemePath . '/theme.json', json_encode([
            'name' => 'Test Child',
            'description' => 'Child theme for tests',
            'version' => '1.0.0',
            'author' => 'Tests',
            'parent' => 'default',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put($childThemePath . '/views/partials/newsletter.blade.php', '<div id="child-newsletter">Child Theme</div>');

        try {
            View::replaceNamespace('theme', [
                $childThemePath . '/views',
                base_path('themes/default/views'),
            ]);
            View::share('errors', new ViewErrorBag());

            $this->assertTrue(view()->exists('theme::partials.newsletter'));
            $this->assertStringContainsString(
                'child-newsletter',
                view('theme::partials.newsletter')->render()
            );
        } finally {
            File::delete($childThemePath . '/views/partials/newsletter.blade.php');
            File::delete($childThemePath . '/theme.json');
            File::deleteDirectory($childThemePath);
        }
    }
}
