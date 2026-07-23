<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemePreviewTest extends TestCase
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

    public function test_admin_can_preview_a_theme_without_changing_the_active_theme(): void
    {
        $previewThemePath = base_path('themes/test-preview');
        File::ensureDirectoryExists($previewThemePath . '/views/partials');
        File::ensureDirectoryExists($previewThemePath . '/public');

        File::put($previewThemePath . '/theme.json', json_encode([
            'name' => 'Test Preview',
            'version' => '1.0.0',
            'author' => 'Tests',
            'parent' => 'default',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put($previewThemePath . '/views/partials/newsletter.blade.php', '<div id="preview-theme">Preview Theme</div>');

        try {
            Setting::updateOrCreate(
                ['key' => 'active_theme'],
                ['value' => 'default', 'type' => 'string']
            );

            $admin = $this->getAdmin();

            $previewResponse = $this->actingAs($admin)
                ->post(route('admin.themes.preview'), [
                    'theme' => 'test-preview',
                ]);

            $previewResponse->assertRedirect(route('home'));

            $this->assertSame('default', Setting::get('active_theme'));

            $response = $this->actingAs($admin)
                ->withSession(['preview_theme' => 'test-preview'])
                ->get(route('home'));
            $response->assertStatus(200);
            $response->assertSee('preview-theme');
            $response->assertSee('Theme preview active');
            $response->assertSee('Exit preview');

            $this->actingAs($admin)
                ->post(route('admin.themes.preview.clear'))
                ->assertRedirect(route('admin.themes.index'));
        } finally {
            File::delete($previewThemePath . '/views/partials/newsletter.blade.php');
            File::delete($previewThemePath . '/theme.json');
            File::deleteDirectory($previewThemePath);
        }
    }
}
