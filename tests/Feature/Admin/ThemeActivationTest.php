<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ThemeActivationTest extends TestCase
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

    public function test_admin_can_activate_an_installed_theme(): void
    {
        Setting::updateOrCreate(
            ['key' => 'active_theme'],
            ['value' => 'default', 'type' => 'string']
        );

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.themes.activate'), [
                'theme' => 'default',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('default', Setting::get('active_theme'));
    }

    public function test_admin_cannot_activate_an_unknown_theme(): void
    {
        Setting::updateOrCreate(
            ['key' => 'active_theme'],
            ['value' => 'default', 'type' => 'string']
        );

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.themes.activate'), [
                'theme' => 'missing-theme',
            ]);

        $response->assertSessionHasErrors('theme');
        $this->assertSame('default', Setting::get('active_theme'));
    }

    public function test_theme_publish_rejects_traversal_theme_names(): void
    {
        $exitCode = Artisan::call('theme:publish', [
            'theme' => '../evil',
        ]);

        $this->assertSame(1, $exitCode);
    }
}
