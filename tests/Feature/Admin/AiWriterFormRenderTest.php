<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Entry;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
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
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('Generate Preview', $html);
        $this->assertStringContainsString('Replace', $html);
        $this->assertStringContainsString('Insert', $html);
        $this->assertStringContainsString('Cancel', $html);
        $this->assertStringContainsString('aiWriterPanel', $html);
    }
}
