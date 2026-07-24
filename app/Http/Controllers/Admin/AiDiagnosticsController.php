<?php

namespace App\Http\Controllers\Admin;

use App\AI\Contracts\AiProvider as AiProviderContract;
use App\AI\Enums\AiStatus;
use App\AI\Services\AiAgentRegistryService;
use App\Http\Controllers\Controller;
use App\Models\AiAgentRun;
use App\Models\AiRequest;
use App\Models\AiToolCall;
use App\Services\SettingService;
use Illuminate\Support\Arr;
use Throwable;

class AiDiagnosticsController extends Controller
{
    public function index(SettingService $settings, AiAgentRegistryService $agents)
    {
        $config = config('ai', []);
        $providers = [];
        $usageSummary = null;

        foreach (Arr::wrap($config['providers'] ?? []) as $name => $providerConfig) {
            $providerConfig = is_array($providerConfig) ? $providerConfig : [];

            $providers[] = $this->buildProviderStatus($name, $providerConfig);
        }

        if (auth()->user()?->hasPermission('view_ai_usage')) {
            $requests = AiRequest::query()->where('created_at', '>=', now()->subDays(30));
            $toolCalls = AiToolCall::query()->where('created_at', '>=', now()->subDays(30));
            $agentRuns = AiAgentRun::query()->where('created_at', '>=', now()->subDays(30));
            $requestCount = (clone $requests)->count();
            $successCount = (clone $requests)->where('status', AiStatus::Succeeded->value)->count();

            $usageSummary = [
                'requests_count' => $requestCount,
                'success_rate' => $requestCount > 0 ? round(($successCount / $requestCount) * 100, 1) : 0.0,
                'failed_requests_count' => (clone $requests)->whereIn('status', [
                    AiStatus::Rejected->value,
                    AiStatus::RateLimited->value,
                    AiStatus::TimedOut->value,
                    AiStatus::Failed->value,
                ])->count(),
                'total_tokens' => (int) (clone $requests)->sum('total_tokens'),
                'estimated_cost' => (string) (clone $requests)->sum('estimated_cost'),
                'average_latency_ms' => (int) round((float) ((clone $requests)->avg('latency_ms') ?? 0)),
                'latest_request_at' => (clone $requests)->latest('created_at')->value('created_at'),
                'failed_tool_calls_count' => (clone $toolCalls)->where('status', 'failed')->count(),
                'tool_calls_count' => (clone $toolCalls)->count(),
                'pending_tool_calls_count' => (clone $toolCalls)->where('approval_state', 'pending')->count(),
                'failed_agent_runs_count' => (clone $agentRuns)->where('status', 'failed')->count(),
                'agent_runs_count' => (clone $agentRuns)->count(),
                'active_agent_runs_count' => (clone $agentRuns)->where('status', 'running')->count(),
            ];
        }

        return view('admin.ai.diagnostics', [
            'config' => $config,
            'providers' => $providers,
            'agents' => $agents->all()->map(static fn ($agent) => $agent->toArray())->all(),
            'usageSummary' => $usageSummary,
            'settings' => [
                'enabled' => $settings->get('ai.enabled', $config['enabled'] ?? false),
                'default_provider' => $settings->get('ai.default_provider', $config['default_provider'] ?? 'fake'),
                'fallback_provider' => $settings->get('ai.fallback_provider', $config['fallback_provider'] ?? 'fake'),
                'writer_enabled' => $settings->get('ai.writer.enabled', true),
                'chat_enabled' => $settings->get('ai.chat.enabled', true),
                'image_enabled' => $settings->get('ai.image.enabled', true),
                'seo_agent_enabled' => $settings->get('ai.agents.seo.enabled', data_get($config, 'agents.seo.is_enabled', true)),
                'marketing_agent_enabled' => $settings->get('ai.agents.marketing.enabled', data_get($config, 'agents.marketing.is_enabled', true)),
                'translation_agent_enabled' => $settings->get('ai.agents.translation.enabled', data_get($config, 'agents.translation.is_enabled', true)),
                'documentation_agent_enabled' => $settings->get('ai.agents.documentation.enabled', data_get($config, 'agents.documentation.is_enabled', true)),
                'support_agent_enabled' => $settings->get('ai.agents.support.enabled', data_get($config, 'agents.support.is_enabled', true)),
                'image_public_generation_enabled' => $settings->get('ai.image.public_generation_enabled', data_get($config, 'providers.fake.image.public_generation_enabled', false)),
                'image_retention_days' => $settings->get('ai.image.retention_days', data_get($config, 'providers.fake.image.retention_days', 30)),
                'seo_agent_seconds' => data_get($config, 'agents.seo.max_seconds', 45),
                'marketing_agent_seconds' => data_get($config, 'agents.marketing.max_seconds', 60),
                'translation_agent_seconds' => data_get($config, 'agents.translation.max_seconds', 45),
                'documentation_agent_seconds' => data_get($config, 'agents.documentation.max_seconds', 60),
                'support_agent_seconds' => data_get($config, 'agents.support.max_seconds', 30),
            ],
        ]);
    }

    protected function buildProviderStatus(string $name, array $providerConfig): array
    {
        $driver = $providerConfig['driver'] ?? null;
        $capabilities = array_values(array_map('strval', $providerConfig['capabilities'] ?? []));
        $imageCapabilities = is_array($providerConfig['image'] ?? null) ? $providerConfig['image'] : [];

        try {
            if (! is_string($driver) || $driver === '') {
                throw new \RuntimeException('Missing driver.');
            }

            $provider = app()->make($driver);

            if (! $provider instanceof AiProviderContract) {
                throw new \RuntimeException("Driver [{$driver}] does not implement the AI provider contract.");
            }

            $status = 'ready';
            $resolvedCapabilities = $provider->capabilities();
        } catch (Throwable $e) {
            $status = 'error';
            $resolvedCapabilities = [];
        }

        return [
            'name' => $name,
            'driver' => is_string($driver) ? $driver : 'n/a',
            'configured_capabilities' => $capabilities,
            'image_capabilities' => $imageCapabilities,
            'resolved_capabilities' => $resolvedCapabilities,
            'status' => $status,
            'enabled' => (bool) ($providerConfig['is_enabled'] ?? true),
        ];
    }
}
