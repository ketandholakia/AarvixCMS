<?php

namespace Tests\Unit\AI;

use App\AI\Exceptions\AiPromptException;
use App\AI\Services\PromptService;
use App\Models\AiPromptVersion;
use PHPUnit\Framework\TestCase;

class PromptServiceTest extends TestCase
{
    public function test_render_template_replaces_exact_placeholders(): void
    {
        $service = new PromptService();

        $rendered = $service->renderTemplate(
            'Hello {{name}}, today is {{day}}.',
            ['name' => 'Aarvix', 'day' => 'Thursday']
        );

        $this->assertSame('Hello Aarvix, today is Thursday.', $rendered);
    }

    public function test_render_template_rejects_missing_and_unknown_variables(): void
    {
        $service = new PromptService();

        $this->expectException(AiPromptException::class);

        $service->renderTemplate(
            'Hello {{name}}.',
            ['name' => 'Aarvix', 'extra' => 'value']
        );
    }

    public function test_render_template_rejects_unresolved_placeholders(): void
    {
        $service = new PromptService();

        $this->expectException(AiPromptException::class);

        $service->renderTemplate(
            'Hello {{name}} and {{missing}}.',
            ['name' => 'Aarvix']
        );
    }

    public function test_render_version_allows_system_and_user_templates_to_use_different_variable_subsets(): void
    {
        $service = new PromptService();
        $version = new AiPromptVersion([
            'system_template' => 'System {{tone}}',
            'user_template' => 'User {{audience}}',
            'variables' => ['tone' => 'friendly', 'audience' => 'editors'],
            'output_schema' => [],
            'version_number' => 1,
        ]);

        $rendered = $service->renderVersion($version, [
            'tone' => 'friendly',
            'audience' => 'editors',
        ]);

        $this->assertSame('System friendly', $rendered['system']);
        $this->assertSame('User editors', $rendered['user']);
    }
}
