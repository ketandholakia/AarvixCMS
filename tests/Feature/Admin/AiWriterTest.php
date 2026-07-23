<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\Page;
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
        $response->assertJsonStructure(['status', 'request_id', 'provider', 'model', 'suggestion', 'response']);
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
        $response->assertSee('Selected sentence only.', false);
        $response->assertDontSee('Whole document text.', false);
    }
}
