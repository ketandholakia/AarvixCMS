<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\AiRequest;
use App\Models\Revision;
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
        $body = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'This is the about page.']],
            ],
        ]);

        $response = $this->actingAs($this->getAdmin())->post(route('admin.pages.store'), [
            'title' => 'About Us',
            'slug' => 'about-us',
            'body' => $body,
            'status' => 'published',
            'template' => 'default',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', ['title' => 'About Us', 'slug' => 'about-us', 'body' => $body]);
    }

    public function test_admin_can_store_page_with_ai_request_uuid_and_track_revision(): void
    {
        $admin = $this->getAdmin();
        $aiRequest = AiRequest::create([
            'request_uuid' => 'page-ai-request-1',
            'feature' => 'writer',
            'status' => 'succeeded',
            'provider' => 'fake',
            'model' => 'fake-writer',
        ]);

        $body = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'AI-assisted page body.']],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'AI Page',
            'slug' => 'ai-page',
            'body' => $body,
            'status' => 'published',
            'template' => 'default',
            'ai_request_uuid' => $aiRequest->request_uuid,
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'ai-page')->firstOrFail();
        $revision = Revision::where('revisionable_type', Page::class)
            ->where('revisionable_id', $page->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($aiRequest->id, $revision->ai_request_id);
        $this->assertSame($aiRequest->request_uuid, $revision->aiRequest?->request_uuid);
    }
}
