<?php

namespace App\Http\Controllers\Admin;

use App\AI\Contracts\AiProvider as AiProviderContract;
use App\AI\Enums\AiStatus;
use App\AI\Services\AiAgentRegistryService;
use App\Http\Controllers\Controller;
use App\Models\AiAgentRun;
use App\Models\AiChatRun;
use App\Models\AiRequest;
use App\Models\AiWorkflowRun;
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
        $ragSummary = null;
        $operationsSummary = null;
        $recentFailures = [];

        foreach (Arr::wrap($config['providers'] ?? []) as $name => $providerConfig) {
            $providerConfig = is_array($providerConfig) ? $providerConfig : [];

            $providers[] = $this->buildProviderStatus($name, $providerConfig);
        }

        if (auth()->user()?->hasPermission('view_ai_usage')) {
            $requests = AiRequest::query()->where('created_at', '>=', now()->subDays(30));
            $toolCalls = AiToolCall::query()->where('created_at', '>=', now()->subDays(30));
            $agentRuns = AiAgentRun::query()->where('created_at', '>=', now()->subDays(30));
            $chatRuns = AiChatRun::query()->where('created_at', '>=', now()->subDays(30));
            $workflowRuns = AiWorkflowRun::query()->where('created_at', '>=', now()->subDays(30));
            $requestCount = (clone $requests)->count();
            $successCount = (clone $requests)->where('status', AiStatus::Succeeded->value)->count();
            $retrievalTurnsCount = (clone $chatRuns)->whereIn('mode', ['knowledge', 'summary', 'policy'])->count();
            $citedTurnsCount = (clone $chatRuns)
                ->whereIn('mode', ['knowledge', 'summary', 'policy'])
                ->get()
                ->filter(static function (AiChatRun $run): bool {
                    return is_array($run->context['citations'] ?? null) && $run->context['citations'] !== [];
                })
                ->count();
            $citationCount = (clone $chatRuns)
                ->whereIn('mode', ['knowledge', 'summary', 'policy'])
                ->get()
                ->sum(static function (AiChatRun $run): int {
                    return is_array($run->context['citations'] ?? null) ? count($run->context['citations']) : 0;
                });
            $noAnswerTurnsCount = (clone $chatRuns)
                ->whereIn('mode', ['knowledge', 'summary', 'policy'])
                ->get()
                ->filter(static function (AiChatRun $run): bool {
                    $hasCitations = is_array($run->context['citations'] ?? null) && $run->context['citations'] !== [];
                    $noAnswerText = str_contains(strtolower((string) $run->response_text), 'could not find any authorized sources');

                    return ! $hasCitations || $noAnswerText;
                })
                ->count();

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

            $ragSummary = [
                'retrieval_turns_count' => $retrievalTurnsCount,
                'cited_turns_count' => $citedTurnsCount,
                'citation_count' => $citationCount,
                'no_answer_turns_count' => $noAnswerTurnsCount,
                'no_answer_rate' => $retrievalTurnsCount > 0 ? round(($noAnswerTurnsCount / $retrievalTurnsCount) * 100, 1) : 0.0,
                'average_citations_per_turn' => $citedTurnsCount > 0 ? round($citationCount / $citedTurnsCount, 1) : 0.0,
            ];

            $toolCallCount = (clone $toolCalls)->count();
            $workflowRunCount = (clone $workflowRuns)->count();

            $operationsSummary = [
                'tool_calls_count' => $toolCallCount,
                'tool_call_success_rate' => $toolCallCount > 0 ? round(((clone $toolCalls)->where('status', 'succeeded')->count() / $toolCallCount) * 100, 1) : 0.0,
                'tool_call_failed_count' => (clone $toolCalls)->where('status', 'failed')->count(),
                'workflow_runs_count' => $workflowRunCount,
                'workflow_success_rate' => $workflowRunCount > 0 ? round(((clone $workflowRuns)->where('status', 'succeeded')->count() / $workflowRunCount) * 100, 1) : 0.0,
                'workflow_failed_count' => (clone $workflowRuns)->where('status', 'failed')->count(),
            ];

            $recentFailures = $this->buildRecentFailures();
        }

        return view('admin.ai.diagnostics', [
            'config' => $config,
            'providers' => $providers,
            'agents' => $agents->all()->map(static fn ($agent) => $agent->toArray())->all(),
            'usageSummary' => $usageSummary,
            'ragSummary' => $ragSummary,
            'operationsSummary' => $operationsSummary,
            'recentFailures' => $recentFailures,
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

    protected function buildRecentFailures(): array
    {
        $failedRequestStatuses = [
            AiStatus::Failed->value,
            AiStatus::TimedOut->value,
            AiStatus::RateLimited->value,
            AiStatus::Rejected->value,
            AiStatus::Cancelled->value,
        ];

        $entries = collect();

        AiRequest::query()
            ->with(['user'])
            ->whereIn('status', $failedRequestStatuses)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->each(function (AiRequest $request) use ($entries): void {
                $entries->push([
                    'type' => 'Request',
                    'title' => $request->feature,
                    'detail' => $request->error_message ?: $request->error_class ?: 'Request failed',
                    'url' => route('admin.ai-requests.show', $request),
                    'occurred_at' => $request->created_at,
                ]);
            });

        AiToolCall::query()
            ->with(['tool'])
            ->where('status', 'failed')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->each(function (AiToolCall $call) use ($entries): void {
                $entries->push([
                    'type' => 'Tool Call',
                    'title' => $call->tool?->key ?? 'Unknown tool',
                    'detail' => $call->error_message ?: $call->error_class ?: 'Tool call failed',
                    'url' => route('admin.ai-tool-calls.show', $call),
                    'occurred_at' => $call->created_at,
                ]);
            });

        AiAgentRun::query()
            ->where('status', 'failed')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->each(function (AiAgentRun $run) use ($entries): void {
                $entries->push([
                    'type' => 'Agent Run',
                    'title' => $run->agent_name,
                    'detail' => $run->error_message ?: $run->error_class ?: 'Agent run failed',
                    'url' => route('admin.ai-agent-runs.show', $run),
                    'occurred_at' => $run->created_at,
                ]);
            });

        return $entries
            ->sortByDesc(static fn (array $entry) => $entry['occurred_at']?->getTimestamp() ?? 0)
            ->values()
            ->take(10)
            ->all();
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
