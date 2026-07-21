<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        return $admin;
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = $this->setUpAdmin();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Site Settings');
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->setUpAdmin();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'New Awesome Site',
            'social_twitter' => 'https://twitter.com/awesomesite',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'New Awesome Site',
        ]);
        
        $this->assertDatabaseHas('settings', [
            'key' => 'social_twitter',
            'value' => 'https://twitter.com/awesomesite',
        ]);
    }
}
