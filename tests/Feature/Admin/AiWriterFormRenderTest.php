<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Entry;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiWriterFormRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_post_form_renders_writer_and_seo_controls(): void
    {
        $post = Post::factory()->create(['author_id' => $this->admin()->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.posts.edit', $post->id));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Generate SEO', $html);
        $this->assertStringContainsString('aiSeoPanel', $html);
    }

    public function test_entry_form_renders_writer_and_seo_controls(): void
    {
        $type = ContentType::factory()->create([
            'name' => 'Portfolio',
            'slug' => 'portfolio',
            'context' => 'post',
            'is_active' => true,
        ]);
        $entry = Entry::factory()->create([
            'content_type_id' => $type->id,
            'author_id' => $this->admin()->id,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.entries.edit', ['type' => $type->slug, 'entry' => $entry->id]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Generate SEO', $html);
        $this->assertStringContainsString('aiSeoPanel', $html);
    }

    public function test_editor_component_renders_the_ai_writer_panel(): void
    {
        $html = view('components.admin.form.editorjs', [
            'name' => 'body',
            'label' => 'Body',
            'value' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Example body.']],
                ],
            ]),
            'required' => false,
            'help' => '',
            'aiContext' => 'post',
            'aiRecordId' => null,
            'aiContentTypeSlug' => null,
            'locale' => 'hi',
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('Generate Preview', $html);
        $this->assertStringContainsString('Replace', $html);
        $this->assertStringContainsString('Insert', $html);
        $this->assertStringContainsString('Cancel', $html);
        $this->assertStringContainsString('aiWriterPanel', $html);
        $this->assertStringContainsString('data-editorjs-placeholder=', $html);
        $this->assertStringContainsString('id="editorjs_body"', $html);
        $this->assertStringContainsString('Media Library', $html);
        $this->assertStringContainsString('Upload Image', $html);
        $this->assertStringContainsString('data-editorjs-media-endpoint=', $html);
        $this->assertStringContainsString('यहां सामग्री लिखें', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
    }

    public function test_ai_writer_panel_badge_tracks_persisted_global_setting(): void
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        config()->set('ai.enabled', true);
        app(SettingService::class)->set('ai.enabled', false, 'ai', 'boolean');

        $disabledHtml = view('admin.partials.ai-writer-panel', [
            'aiContext' => 'post',
            'aiRecordId' => null,
            'aiContentTypeSlug' => null,
        ])->render();

        $this->assertStringContainsString('Disabled', $disabledHtml);

        app(SettingService::class)->set('ai.enabled', true, 'ai', 'boolean');

        $enabledHtml = view('admin.partials.ai-writer-panel', [
            'aiContext' => 'post',
            'aiRecordId' => null,
            'aiContentTypeSlug' => null,
        ])->render();

        $this->assertStringContainsString('Ready', $enabledHtml);
        $this->assertStringContainsString('Generate Preview', $enabledHtml);
    }
}
