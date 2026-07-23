<?php

namespace Tests\Unit\AI;

use App\AI\Services\AiAgentRegistryService;
use App\Services\SettingService;
use Tests\TestCase;

class AiAgentRegistryServiceTest extends TestCase
{
    public function test_it_returns_versioned_agent_definitions(): void
    {
        config()->set('ai.agents.seo', [
            'version' => 2,
            'name' => 'SEO Agent',
            'description' => 'Test agent',
            'prompt' => 'ai.agents.seo.v2',
            'tools' => ['content.summary', 'seo.propose'],
            'memory' => ['store' => 'session'],
            'permissions' => ['use_ai_writer'],
            'model_policy' => ['primary' => 'writer'],
            'budgets' => ['max_tokens' => 1200, 'max_cost' => '0.25'],
            'max_steps' => 5,
            'is_enabled' => true,
        ]);

        $settings = new class extends SettingService
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }
        };

        $service = new AiAgentRegistryService($settings);

        $agent = $service->find('seo');

        $this->assertNotNull($agent);
        $this->assertSame('seo', $agent->key);
        $this->assertSame(2, $agent->version);
        $this->assertSame('ai.agents.seo.v2', $agent->promptKey);
        $this->assertSame(['content.summary', 'seo.propose'], $agent->tools);
        $this->assertSame(['use_ai_writer'], $agent->permissions);
        $this->assertSame(5, $agent->maxSteps);
        $this->assertSame(60, $agent->maxSeconds);
        $this->assertTrue($agent->isEnabled);
    }

    public function test_it_respects_runtime_disable_overrides(): void
    {
        config()->set('ai.agents.support', [
            'version' => 1,
            'name' => 'Support Agent',
            'prompt' => 'ai.agents.support.v1',
            'tools' => ['content.search'],
            'permissions' => [],
            'is_enabled' => true,
        ]);

        $settings = new class extends SettingService
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'ai.agents.support.enabled' ? false : $default;
            }
        };

        $service = new AiAgentRegistryService($settings);

        $this->assertFalse($service->isEnabled('support'));
        $this->assertCount(0, $service->all(true));
    }

    public function test_it_merges_runtime_model_and_budget_overrides(): void
    {
        config()->set('ai.agents.seo', [
            'version' => 1,
            'name' => 'SEO Agent',
            'prompt' => 'ai.agents.seo.v1',
            'tools' => ['content.summary', 'seo.propose'],
            'model_policy' => [
                'primary' => 'writer',
                'fallback' => 'chat',
                'temperature' => 0.4,
            ],
            'budgets' => [
                'max_tokens' => 1800,
                'max_cost' => '0.50',
            ],
            'max_steps' => 3,
            'max_seconds' => 45,
            'is_enabled' => true,
        ]);

        $settings = new class extends SettingService
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    'ai.agents.seo.primary_model' => 'gpt-4.1',
                    'ai.agents.seo.fallback_model' => 'gpt-4.1-mini',
                    'ai.agents.seo.temperature' => '0.3',
                    'ai.agents.seo.max_tokens' => '2048',
                    'ai.agents.seo.max_cost' => '0.75',
                    'ai.agents.seo.max_steps' => '5',
                    'ai.agents.seo.max_seconds' => '90',
                    default => $default,
                };
            }
        };

        $service = new AiAgentRegistryService($settings);
        $agent = $service->find('seo');

        $this->assertNotNull($agent);
        $this->assertSame('gpt-4.1', $agent->modelPolicy['primary']);
        $this->assertSame('gpt-4.1-mini', $agent->modelPolicy['fallback']);
        $this->assertSame(0.3, $agent->modelPolicy['temperature']);
        $this->assertSame(2048, $agent->budgets['max_tokens']);
        $this->assertSame('0.75', $agent->budgets['max_cost']);
        $this->assertSame(5, $agent->maxSteps);
        $this->assertSame(90, $agent->maxSeconds);
    }
}
