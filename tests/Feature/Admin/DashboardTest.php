<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_active_admin_can_view_dashboard(): void
    {
        // Seed RBAC setup
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSeeText('Total Posts');
    }
}
