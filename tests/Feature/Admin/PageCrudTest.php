<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    public function test_admin_can_view_pages_index(): void
    {
        Page::factory()->count(3)->create();

        $response = $this->actingAs($this->getAdmin())->get(route('admin.pages.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.pages.index');
    }

    public function test_admin_can_store_page(): void
    {
        $response = $this->actingAs($this->getAdmin())->post(route('admin.pages.store'), [
            'title' => 'About Us',
            'slug' => 'about-us',
            'body' => 'This is the about page.',
            'status' => 'published',
            'template' => 'default',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', ['title' => 'About Us', 'slug' => 'about-us']);
    }
}
