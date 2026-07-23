<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\ThemeManager;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    public function test_admin_can_update_theme_settings_for_active_theme(): void
    {
        $admin = $this->getAdmin();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertStatus(200)
            ->assertSee('Theme Settings for default')
            ->assertSee('Logo URL')
            ->assertSee('Accent Color')
            ->assertSee('Font Family')
            ->assertSee('Enable dark mode by default');

        $this->actingAs($admin)
            ->put(route('admin.themes.settings.update'), [
                'logo_url' => 'https://example.com/logo.svg',
                'accent_color' => '#112233',
                'font_family' => 'Inter, sans-serif',
                'dark_mode_enabled' => '1',
            ])
            ->assertRedirect(route('admin.themes.index'));

        $activeTheme = app(ThemeManager::class)->getActiveTheme();

        $this->assertDatabaseHas('theme_settings', [
            'theme' => $activeTheme,
            'key' => 'logo_url',
            'value' => 'https://example.com/logo.svg',
        ]);

        $this->assertSame('https://example.com/logo.svg', app(\App\Services\ThemeSettingsService::class)->get($activeTheme, 'logo_url'));
        $this->assertSame('#112233', app(\App\Services\ThemeSettingsService::class)->get($activeTheme, 'accent_color'));
        $this->assertSame('Inter, sans-serif', app(\App\Services\ThemeSettingsService::class)->get($activeTheme, 'font_family'));
        $this->assertTrue(app(\App\Services\ThemeSettingsService::class)->get($activeTheme, 'dark_mode_enabled'));
    }

    public function test_admin_can_update_theme_sections(): void
    {
        $admin = $this->getAdmin();
        $themeSettings = app(ThemeSettingsService::class);
        $activeTheme = app(ThemeManager::class)->getActiveTheme();

        $this->actingAs($admin)
            ->put(route('admin.themes.settings.update'), [
                'sections' => [
                    'sidebar' => '<div class="p-4">Custom sidebar content</div>',
                    'footer_widgets' => '<div class="p-4">Custom footer widget</div>',
                ],
            ])
            ->assertRedirect(route('admin.themes.index'));

        $this->assertSame('<div class="p-4">Custom sidebar content</div>', $themeSettings->getSection($activeTheme, 'sidebar'));
        $this->assertSame('<div class="p-4">Custom footer widget</div>', $themeSettings->getSection($activeTheme, 'footer_widgets'));
    }

    public function test_theme_sections_are_sanitized_on_save(): void
    {
        $admin = $this->getAdmin();
        $themeSettings = app(ThemeSettingsService::class);
        $activeTheme = app(ThemeManager::class)->getActiveTheme();

        $this->actingAs($admin)
            ->put(route('admin.themes.settings.update'), [
                'sections' => [
                    'sidebar' => '<div>Safe</div><script>alert(1)</script>',
                ],
            ])
            ->assertRedirect(route('admin.themes.index'));

        $saved = $themeSettings->getSection($activeTheme, 'sidebar');

        $this->assertStringContainsString('<div>Safe</div>', $saved);
        $this->assertStringNotContainsString('<script>', $saved);
    }

    public function test_theme_settings_update_returns_validation_errors_for_json_requests(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->putJson(route('admin.themes.settings.update'), [
                'logo_url' => 'not-a-url',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The logo url field must be a valid URL.',
        ]);
        $this->assertArrayHasKey('errors', $response->json());
        $this->assertArrayHasKey('logo_url', $response->json('errors'));
    }
}
