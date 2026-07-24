<?php

namespace Tests\Feature\Admin;

use App\Models\AiPrompt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_cannot_create_prompt_with_duplicate_key(): void
    {
        $admin = $this->admin();

        $existing = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Existing prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $existing->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.ai-prompts.create'))
            ->post(route('admin.ai-prompts.store'), [
                'prompt_key' => 'writer.rewrite',
                'category' => 'writer',
                'title' => 'Rewrite Copy',
                'description' => 'Duplicate prompt key',
                'system_template' => 'Return a sharper rewrite.',
                'user_template' => '',
                'variables_json' => '{}',
                'output_schema_json' => '{}',
                'change_summary' => 'Duplicate attempt',
                'is_enabled' => 1,
            ]);

        $response->assertRedirect(route('admin.ai-prompts.create'));
        $response->assertSessionHasErrors(['prompt_key']);
        $this->assertSame(1, AiPrompt::query()->where('prompt_key', 'writer.rewrite')->count());
    }

    public function test_admin_cannot_update_prompt_to_duplicate_key(): void
    {
        $admin = $this->admin();

        $primary = AiPrompt::create([
            'prompt_key' => 'writer.rewrite',
            'category' => 'writer',
            'title' => 'Rewrite',
            'description' => 'Primary prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $primary->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a polished rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $secondary = AiPrompt::create([
            'prompt_key' => 'writer.shorten',
            'category' => 'writer',
            'title' => 'Shorten',
            'description' => 'Secondary prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $secondary->versions()->create([
            'version_number' => 1,
            'system_template' => 'Return a concise rewrite.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.ai-prompts.edit', $secondary))
            ->put(route('admin.ai-prompts.update', $secondary), [
                'prompt_key' => 'writer.rewrite',
                'category' => 'writer',
                'title' => 'Shorten',
                'description' => 'Attempt duplicate key',
                'system_template' => 'Return a more concise rewrite.',
                'user_template' => '',
                'variables_json' => '{}',
                'output_schema_json' => '{}',
                'change_summary' => 'Duplicate attempt',
                'is_enabled' => 1,
            ]);

        $response->assertRedirect(route('admin.ai-prompts.edit', $secondary));
        $response->assertSessionHasErrors(['prompt_key']);
        $this->assertSame(1, AiPrompt::query()->where('prompt_key', 'writer.rewrite')->count());
        $this->assertSame(1, AiPrompt::query()->where('prompt_key', 'writer.shorten')->count());
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

    public function test_admin_can_export_prompt_as_json(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.exportable',
            'category' => 'writer',
            'title' => 'Exportable',
            'description' => 'Exportable prompt',
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

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.export', $prompt));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertSeeText('writer.exportable');
        $response->assertSeeText('Return a sharper rewrite.');
        $response->assertSeeText('Use a concise tone.');
        $response->assertSeeText('Second version');
    }

    public function test_admin_can_import_prompt_from_exported_json(): void
    {
        $admin = $this->admin();

        $payload = [
            'prompt' => [
                'prompt_key' => 'writer.imported',
                'category' => 'writer',
                'title' => 'Imported Prompt',
                'description' => 'Imported from JSON',
                'active_version_number' => 2,
                'output_schema' => ['type' => 'object'],
                'is_enabled' => true,
            ],
            'versions' => [
                [
                    'version_number' => 1,
                    'system_template' => 'Return a polished rewrite.',
                    'user_template' => null,
                    'variables' => [],
                    'output_schema' => [],
                    'change_summary' => 'Initial version',
                ],
                [
                    'version_number' => 2,
                    'system_template' => 'Return a sharper rewrite in a {{tone}} tone.',
                    'user_template' => null,
                    'variables' => ['tone' => 'direct'],
                    'output_schema' => ['type' => 'object'],
                    'change_summary' => 'Second version',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.ai-prompts.import'))
            ->post(route('admin.ai-prompts.import.store'), [
                'payload_json' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]);

        $response->assertRedirect();

        $prompt = AiPrompt::query()->where('prompt_key', 'writer.imported')->firstOrFail();
        $this->assertSame('Imported Prompt', $prompt->title);
        $this->assertSame(2, $prompt->active_version_number);
        $this->assertSame(2, $prompt->versions()->count());
    }

    public function test_admin_can_import_prompt_from_uploaded_json_file(): void
    {
        $admin = $this->admin();

        $payload = [
            'prompt' => [
                'prompt_key' => 'writer.uploaded',
                'category' => 'writer',
                'title' => 'Uploaded Prompt',
                'description' => 'Imported from file',
                'active_version_number' => 1,
                'output_schema' => [],
                'is_enabled' => false,
            ],
            'versions' => [
                [
                    'version_number' => 1,
                    'system_template' => 'Return a polished rewrite.',
                    'user_template' => null,
                    'variables' => [],
                    'output_schema' => [],
                    'change_summary' => 'Initial version',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.ai-prompts.import'))
            ->post(route('admin.ai-prompts.import.store'), [
                'payload_file' => UploadedFile::fake()->createWithContent('prompt.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
            ]);

        $response->assertRedirect();

        $prompt = AiPrompt::query()->where('prompt_key', 'writer.uploaded')->firstOrFail();
        $this->assertSame('Uploaded Prompt', $prompt->title);
        $this->assertSame(1, $prompt->active_version_number);
        $this->assertSame(1, $prompt->versions()->count());
    }

    public function test_admin_can_view_prompt_import_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.ai-prompts.import'));

        $response->assertOk();
        $response->assertSee('Import Prompt JSON', false);
        $response->assertSee('Paste the JSON exported from a prompt detail page.', false);
        $response->assertSee('Upload JSON file', false);
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

    public function test_admin_can_view_bulk_actions_on_prompt_library(): void
    {
        $admin = $this->admin();

        $prompt = AiPrompt::create([
            'prompt_key' => 'writer.bulk',
            'category' => 'writer',
            'title' => 'Bulk Prompt',
            'description' => 'Used for bulk controls',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Bulk template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-prompts.index'));

        $response->assertOk();
        $response->assertSee('Bulk actions');
        $response->assertSee('Enable Selected', false);
        $response->assertSee('Disable Selected', false);
        $response->assertSee('name="prompt_ids[]"', false);
    }

    public function test_admin_can_bulk_disable_selected_prompts(): void
    {
        $admin = $this->admin();

        $firstPrompt = AiPrompt::create([
            'prompt_key' => 'writer.bulk-one',
            'category' => 'writer',
            'title' => 'Bulk One',
            'description' => 'First bulk prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $firstPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'First template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $secondPrompt = AiPrompt::create([
            'prompt_key' => 'writer.bulk-two',
            'category' => 'writer',
            'title' => 'Bulk Two',
            'description' => 'Second bulk prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => true,
        ]);

        $secondPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Second template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.ai-prompts.bulk-update'), [
            'action' => 'disable',
            'prompt_ids' => [$firstPrompt->id, $secondPrompt->id],
        ]);

        $response->assertRedirect(route('admin.ai-prompts.index'));
        $response->assertSessionHas('success', '2 prompts disabled successfully.');

        $firstPrompt->refresh();
        $secondPrompt->refresh();
        $this->assertFalse($firstPrompt->is_enabled);
        $this->assertFalse($secondPrompt->is_enabled);
    }

    public function test_admin_can_bulk_enable_selected_prompts(): void
    {
        $admin = $this->admin();

        $firstPrompt = AiPrompt::create([
            'prompt_key' => 'writer.bulk-disabled-one',
            'category' => 'writer',
            'title' => 'Bulk Disabled One',
            'description' => 'First disabled bulk prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => false,
        ]);

        $firstPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'First disabled template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $secondPrompt = AiPrompt::create([
            'prompt_key' => 'writer.bulk-disabled-two',
            'category' => 'writer',
            'title' => 'Bulk Disabled Two',
            'description' => 'Second disabled bulk prompt',
            'active_version_number' => 1,
            'output_schema' => [],
            'is_enabled' => false,
        ]);

        $secondPrompt->versions()->create([
            'version_number' => 1,
            'system_template' => 'Second disabled template.',
            'user_template' => null,
            'variables' => [],
            'output_schema' => [],
            'change_summary' => 'Initial version',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.ai-prompts.bulk-update'), [
            'action' => 'enable',
            'prompt_ids' => [$firstPrompt->id],
        ]);

        $response->assertRedirect(route('admin.ai-prompts.index'));
        $response->assertSessionHas('success', '1 prompt enabled successfully.');

        $firstPrompt->refresh();
        $secondPrompt->refresh();
        $this->assertTrue($firstPrompt->is_enabled);
        $this->assertFalse($secondPrompt->is_enabled);
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
