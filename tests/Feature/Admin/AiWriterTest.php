<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\Page;
use App\Models\Revision;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiWriterTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_generate_ai_writer_preview_for_a_post(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $post = Post::factory()->create(['author_id' => $this->admin()->id]);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'record_id' => $post->id,
            'operation' => 'rewrite',
            'tone' => 'friendly',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Original draft text.']],
                ],
            ]),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['status', 'request_id', 'provider', 'model', 'suggestion', 'preview', 'response']);
        $response->assertJsonPath('preview.mode', 'replace');
        $response->assertJsonPath('preview.blocks.0.type', 'paragraph');
        $response->assertSee('Rewritten draft', false);
    }

    public function test_admin_can_generate_ai_writer_preview_for_a_new_page(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.writer.generate'), [
            'context' => 'page',
            'operation' => 'summarize',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Some long page copy.']],
                ],
            ]),
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'succeeded');
        $response->assertJsonPath('provider', 'fake');
        $response->assertJsonPath('preview.blocks.0.type', 'list');
    }

    public function test_admin_can_generate_ai_writer_preview_for_selected_text_only(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $post = Post::factory()->create(['author_id' => $this->admin()->id]);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'record_id' => $post->id,
            'operation' => 'rewrite',
            'scope' => 'selection',
            'selection' => 'Selected sentence only.',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Whole document text.']],
                ],
            ]),
        ]);

        $response->assertOk();
        $response->assertJsonPath('preview.mode', 'insert');
        $response->assertJsonPath('preview.actions.0', 'insert');
        $response->assertSee('Selected sentence only.', false);
        $response->assertDontSee('Whole document text.', false);
    }

    public function test_author_cannot_generate_ai_writer_preview_for_someone_elses_post(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $authorRole = Role::where('name', 'Author')->first();
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach($authorRole);

        $otherAuthor = User::factory()->create(['is_active' => true]);
        $post = Post::factory()->create(['author_id' => $otherAuthor->id]);

        $response = $this->actingAs($author)->postJson(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'record_id' => $post->id,
            'operation' => 'rewrite',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Protected draft text.']],
                ],
            ]),
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_generate_seo_preview_for_a_post(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $post = Post::factory()->create([
            'author_id' => $this->admin()->id,
            'title' => 'SEO Ready Title',
        ]);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'record_id' => $post->id,
            'title' => 'SEO Ready Title',
            'operation' => 'seo',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'This article explains SEO planning for editors.']],
                ],
            ]),
        ]);

        $response->assertOk();
        $response->assertJsonPath('preview.seo.meta_title', 'SEO Ready Title');
        $response->assertJsonPath('preview.seo.slug', 'seo-ready-title');
        $response->assertJsonCount(2, 'preview.seo.warnings');
        $response->assertJsonPath('preview.seo.warnings.0', 'Meta title is short. Aim for 25 to 60 characters.');
    }

    public function test_ai_generated_post_changes_are_captured_in_revision_history(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $admin = $this->admin();
        $post = Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'SEO Ready Title',
            'slug' => 'seo-ready-title',
            'status' => 'draft',
        ]);

        $preview = $this->actingAs($admin)->postJson(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'record_id' => $post->id,
            'title' => 'SEO Ready Title',
            'operation' => 'seo',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'This article explains SEO planning for editors.']],
                ],
            ]),
        ])->json('preview.seo');

        $this->actingAs($admin)->put(route('admin.posts.update', $post->id), [
            'title' => 'SEO Ready Title',
            'slug' => $preview['slug'],
            'excerpt' => 'An edited excerpt that mirrors AI-assisted changes.',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'This article explains SEO planning for editors.']],
                ],
            ]),
            'status' => 'draft',
            'meta_title' => $preview['meta_title'],
            'meta_description' => $preview['meta_description'],
            'published_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.posts.index'));

        $this->assertSame(2, $post->fresh()->revisions()->count());

        $revision = Revision::where('revisionable_type', Post::class)
            ->where('revisionable_id', $post->id)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($revision);
        $this->assertNotNull($revision->after_attributes);
        $this->assertSame($preview['meta_title'], $revision->after_attributes['meta_title'] ?? null);
        $this->assertSame($preview['meta_description'], $revision->after_attributes['meta_description'] ?? null);
    }
}
