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
}
