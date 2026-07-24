<?php

namespace Tests\Feature\Admin;

use App\Models\AiPrompt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPromptCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_create_prompt_with_initial_version(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.ai-prompts.store'), [
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'system_template' => 'Return a polished rewrite.',
            'user_template' => '',
            'variables_json' => '{}',
            'output_schema_json' => '{}',
            'change_summary' => 'Initial version',
            'is_enabled' => 1,
        ]);

        $response->assertRedirect();

        $prompt = AiPrompt::query()->first();
        $this->assertNotNull($prompt);
        $this->assertSame(1, $prompt->versions()->count());
        $this->assertSame(1, $prompt->active_version_number);
    }

    public function test_admin_can_view_prompt_create_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.ai-prompts.create'));

        $response->assertOk();
        $response->assertSee('Create Prompt', false);
        $response->assertSee('Supports strict {{variable}} placeholders.', false);
        $response->assertSee('Create version 1', false);
        $response->assertSee('Active version', false);
        $response->assertSee('Current versions', false);
    }

    public function test_admin_can_view_prompt_create_form_with_a_session_cookie(): void
    {
        $admin = $this->admin();

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('http://localhost/admin');

        $response = $this->get(route('admin.ai-prompts.create'));

        $response->assertOk();
        $response->assertSee('Create Prompt', false);
        $response->assertSee('Supports strict {{variable}} placeholders.', false);
        $response->assertSee('Create version 1', false);
        $response->assertSee('Active version', false);
        $response->assertSee('Current versions', false);
    }

    public function test_admin_sees_validation_summary_when_prompt_form_is_invalid(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.ai-prompts.create'))
            ->followingRedirects()
            ->post(route('admin.ai-prompts.store'), [
                'prompt_key' => '',
                'category' => '',
                'title' => '',
                'description' => 'Invalid prompt',
                'system_template' => '',
                'user_template' => '',
                'variables_json' => 'not-json',
                'output_schema_json' => '{}',
                'change_summary' => '',
                'is_enabled' => 1,
            ]);

        $response->assertOk();
        $response->assertSee('Fix the highlighted fields before saving.', false);
        $response->assertSee('The prompt key field is required.', false);
        $response->assertSee('The category field is required.', false);
        $response->assertSee('The title field is required.', false);
        $response->assertSee('The variables json field must be a valid JSON string.', false);
    }

    public function test_admin_can_view_prompt_edit_form_version_summary(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.edit', $prompt));

        $response->assertOk();
        $response->assertSee('Edit Prompt', false);
        $response->assertSee('Save New Version', false);
        $response->assertSee('Create version 2', false);
        $response->assertSee('Active version', false);
        $response->assertSee('Current versions', false);
    }

    public function test_admin_can_view_prompt_library_summary(): void
    {
        $admin = $this->admin();

        $enabledPrompt = AiPrompt::create([
            'prompt_key' => 'writer.summary',
            'category' => 'writer',
            'title' => 'Summary',
            'description' => 'Enabled prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $enabledPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Summarize content.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $disabledPrompt = AiPrompt::create([
            'prompt_key' => 'writer.disabled',
            'category' => 'writer',
            'title' => 'Disabled',
            'description' => 'Disabled prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => false,
        ]);

        $disabledPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Disabled template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.index'));

        $response->assertOk();
        $response->assertSee('Prompt Library');
        $response->assertSee('Total prompts');
        $response->assertSee('Enabled');
        $response->assertSee('Disabled');
        $response->assertSee('Total versions');
        $response->assertSee('writer.summary');
        $response->assertSee('writer.disabled');
    }

    public function test_admin_can_filter_prompt_library_by_search_and_state(): void
    {
        $admin = $this->admin();

        $enabledPrompt = AiPrompt::create([
            'prompt_key' => 'writer.searchable',
            'category' => 'writer',
            'title' => 'Searchable Prompt',
            'description' => 'Matches search',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $enabledPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Searchable template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $disabledPrompt = AiPrompt::create([
            'prompt_key' => 'support.unused',
            'category' => 'support',
            'title' => 'Unused Prompt',
            'description' => 'Should not match',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => false,
        ]);

        $disabledPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Unused template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.index', [
            'q' => 'searchable',
            'state' => 'enabled',
        ]));

        $response->assertOk();
        $response->assertSee('Total prompts');
        $response->assertSee('1');
        $response->assertSee('Searchable Prompt');
        $response->assertDontSee('Unused Prompt');
        $response->assertSee('writer.searchable');
    }

    public function test_admin_can_create_new_prompt_versions_and_rollback(): void
    {
        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.ai-prompts.update', $prompt), [
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'system_template' => 'Return a sharper rewrite.',
            'user_template' => '',
            'variables_json' => '{}',
            'output_schema_json' => '{}',
            'change_summary' => 'Second version',
            'is_enabled' => 1,
        ]);

        $response->assertRedirect(route('admin.ai-prompts.show', $prompt));

        $prompt->refresh();
        $this->assertSame(2, $prompt->active_version_number);
        $this->assertSame(2, $prompt->versions()->count());

        $rollback = $this->actingAs($this->admin())->post(route('admin.ai-prompts.rollback', [$prompt, 1]));

        $rollback->assertRedirect(route('admin.ai-prompts.show', $prompt));

        $prompt->refresh();
        $this->assertSame(1, $prompt->active_version_number);
    }

    public function test_admin_can_view_prompt_history_summary(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'active_version_number' => 2,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $prompt->versions()->create([
            'version_number' => 2,
            'system_template' => 'Return a sharper rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Second version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.show', $prompt));

        $response->assertOk();
        $response->assertSee('Prompt History');
        $response->assertSee('Active version');
        $response->assertSee('Total versions');
        $response->assertSee('Latest version created');
        $response->assertSee('Enabled');
        $response->assertSeeText('Active version 2');
        $response->assertSee('Second version');
        $response->assertSee('Initial version');
    }

    public function test_admin_can_duplicate_a_prompt_with_latest_version(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'active_version_number' => 2,
            'output_schema' => ['type' => 'object'],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $prompt->versions()->create([
            'version_number' => 2,
            'system_template' => 'Return a sharper rewrite.',
            'user_template' => 'Use a concise tone.',
            'variables' => ['tone' => 'direct'],
            'output_schema' => ['type' => 'object'],
            'change_summary' => 'Second version',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.ai-prompts.duplicate', $prompt));

        $response->assertRedirect();

        $clone = AiPrompt::query()->where('prompt_key', 'writer.rewrite-copy')->firstOrFail();
        $this->assertSame('writer', $clone->category);
        $this->assertSame('Rewrite Copy', $clone->title);
        $this->assertFalse($clone->is_enabled);
        $this->assertSame(1, $clone->active_version_number);
        $this->assertSame(1, $clone->versions()->count());

        $cloneVersion = $clone->versions()->firstOrFail();
        $this->assertSame('Return a sharper rewrite.', $cloneVersion->system_template);
        $this->assertSame('Use a concise tone.', $cloneVersion->user_template);
        $this->assertSame('Cloned from writer.rewrite version 2', $cloneVersion->change_summary);
    }

    public function test_admin_can_compare_prompt_versions(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Rewrite helper',
            'active_version_number' => 2,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => ['tone' => 'calm'],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $prompt->versions()->create([
            'version_number' => 2,
            'system_template' => 'Return a sharper rewrite.',
            'user_template' => 'Use a concise tone.',
            'variables' => ['tone' => 'direct'],
            'output_schema' => ['type' => 'object'],
            'change_summary' => 'Second version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.compare', [$prompt, 1]));

        $response->assertOk();
        $response->assertSee('Compare Prompt Versions');
        $response->assertSee('Comparison Details');
        $response->assertSee('System template');
        $response->assertSee('Variables');
        $response->assertSee('Output schema');
        $response->assertSee('Change summary');
        $response->assertSee('Changed');
        $response->assertSee('Return a polished rewrite.');
        $response->assertSee('Return a sharper rewrite.');
    }
}
