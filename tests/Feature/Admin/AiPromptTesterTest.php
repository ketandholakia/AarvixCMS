<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\AiPrompt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPromptTesterTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_open_the_prompt_tester(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $prompt = $this->createPrompt();

        $response = $this->actingAs($this->admin())->get(route('admin.ai-prompts.test', $prompt));

        $response->assertOk();
        $response->assertSeeText('Prompt Tester');
        $response->assertSeeText('Render the active prompt version');
        $response->assertSeeText('topic');
        $response->assertSeeText('audience');
        $response->assertSeeText('input');
    }

    public function test_admin_can_run_the_active_prompt_version_against_the_selected_provider(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $prompt = $this->createPrompt();

        $response = $this->actingAs($this->admin())->post(route('admin.ai-prompts.test.run', $prompt), [
            'provider' => 'fake',
            'model' => 'fake-writer',
            'runtime_input' => 'Draft the release note.',
            'variables_json' => json_encode([
                'topic' => 'Launch Day',
                'audience' => 'editors',
                'input' => '',
            ]),
        ]);

        $response->assertOk();
        $response->assertSeeText('Rendered Prompt');
        $response->assertSeeText('Result');
        $response->assertSeeText('Rewritten draft');
        $response->assertSeeText('Launch Day');
        $response->assertSeeText('Draft the release note.');
        $response->assertSeeText('fake');
        $response->assertSeeText('fake-writer');
    }

    protected function createPrompt(): AiPrompt
    {
        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.launch-note',
            'category' => 'writer',
            'title' => 'Launch Note',
            'description' => 'Prompt tester fixture.',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'You are a helpful assistant for {{topic}}.',
            'user_template' => 'Write a concise note for {{audience}} using this input: {{input}}.',
            'variables' => [
                'topic' => 'launch planning',
                'audience' => 'editors',
                'input' => '',
            ],
            'output_schema' => [],
            'change_summary' => 'Initial tester prompt',
        ]);

        return $prompt;
    }
}
