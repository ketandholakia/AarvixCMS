<?php

namespace Tests\Feature\AI;

use App\Models\AiTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_tool_seeder_populates_initial_tool_definitions(): void
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\AiToolSeeder']);

        $this->assertDatabaseHas('ai_tools', [
            'key' => 'content.search',
            'name' => 'Search Content',
            'required_permission' => 'view_posts',
        ]);

        $this->assertDatabaseCount('ai_tools', 6);

        $tool = AiTool::query()->where('key', 'seo.propose')->firstOrFail();

        $this->assertSame('review', $tool->confirmation_policy);
        $this->assertSame('write', $tool->risk_classification);
        $this->assertTrue($tool->is_enabled);
        $this->assertSame(30, $tool->timeout_seconds);
        $this->assertSame(20, $tool->rate_limit_per_minute);
        $this->assertSame('minimal', $tool->audit_redaction_policy);
    }
}
