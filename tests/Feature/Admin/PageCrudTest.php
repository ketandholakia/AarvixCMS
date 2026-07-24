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
        $adminRole = Role::where('name', 'Admin')->first();
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

    public function test_admin_can_view_page_create_form_with_body_editor(): void
    {
        $response = $this->actingAs($this->getAdmin())->get(route('admin.pages.create'));

        $response->assertStatus(200);
        $response->assertSee('Page Body (EN)');
        $response->assertSee('name="body"', false);
        $response->assertSee('editorjs_body', false);
        $response->assertSee('@editorjs/marker', false);
        $response->assertSee('@editorjs/underline', false);
        $response->assertSee('@editorjs/table', false);
        $response->assertSee('@editorjs/embed', false);
        $response->assertSee('@editorjs/warning', false);
        $response->assertSee("inlineToolbar: ['link', 'marker', 'bold', 'italic', 'underline']", false);
        $response->assertSee('Generate Preview');
        $response->assertSee('AI Writer');

        $html = $response->getContent();

        $this->assertStringContainsString('data-editorjs-placeholder=', $html);
        $this->assertStringContainsString(
            'Write the page content here. Use "/" or the + button to add blocks.',
            html_entity_decode($html, ENT_QUOTES | ENT_HTML5)
        );
        $this->assertMatchesRegularExpression('/name="title"[^>]*required/s', $html);
        $this->assertDoesNotMatchRegularExpression('/name="translations\[hi\]\[title\]"[^>]*required/s', $html);
        $this->assertDoesNotMatchRegularExpression('/name="translations\[gu\]\[title\]"[^>]*required/s', $html);
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
