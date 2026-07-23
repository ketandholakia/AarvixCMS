<?php

namespace Tests\Feature\AI;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_prompt_seeder_creates_idempotent_writer_prompts(): void
    {
        $this->artisan('db:seed', ['--class' => 'AiPromptSeeder']);

        $this->assertDatabaseCount('ai_prompts', 6);
        $this->assertDatabaseCount('ai_prompt_versions', 6);

        $seoPrompt = AiPrompt::query()->where('prompt_key', 'writer.seo')->first();
        $this->assertNotNull($seoPrompt);
        $this->assertTrue($seoPrompt->is_enabled);
        $this->assertSame(1, $seoPrompt->active_version_number);

        $seoVersion = AiPromptVersion::query()
            ->where('ai_prompt_id', $seoPrompt->id)
            ->where('version_number', 1)
            ->first();

        $this->assertNotNull($seoVersion);
        $this->assertArrayHasKey('meta_title', $seoVersion->output_schema['properties']);
        $this->assertArrayHasKey('warnings', $seoVersion->output_schema['properties']);

        $this->artisan('db:seed', ['--class' => 'AiPromptSeeder']);

        $this->assertDatabaseCount('ai_prompts', 6);
        $this->assertDatabaseCount('ai_prompt_versions', 6);
    }
}
