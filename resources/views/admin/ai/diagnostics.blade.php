@extends('layouts.admin')

@section('header', 'AI Diagnostics')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.ai-requests.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
            AI requests
        </a>
        <a href="{{ route('admin.ai-tool-calls.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
            Tool calls
        </a>
        <a href="{{ route('admin.ai-agent-runs.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
            Agent runs
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">AI platform</div>
            <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                {{ ($settings['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Default provider</div>
            <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                {{ $settings['default_provider'] ?? 'n/a' }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Fallback provider</div>
            <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                {{ $settings['fallback_provider'] ?? 'n/a' }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Configured providers</div>
            <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                {{ count($providers) }}
            </div>
        </div>
    </div>

    @if($usageSummary !== null)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Usage Summary</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Last 30 days of AI request activity.</p>
                </div>
                <a href="{{ route('admin.ai-requests.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Open requests
                </a>
            </div>
            <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Requests</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['requests_count']) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Success Rate</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['success_rate'], 1) }}%</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tokens</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['total_tokens']) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Estimated Cost</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format((float) $usageSummary['estimated_cost'], 4) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Avg Latency</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['average_latency_ms']) }} ms</div>
                </div>
            </div>
            <div class="grid gap-4 border-t border-gray-200 px-6 py-5 md:grid-cols-3 dark:border-gray-800">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tool Calls</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['tool_calls_count']) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($usageSummary['pending_tool_calls_count']) }} pending approval</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($usageSummary['failed_tool_calls_count']) }} failed</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Agent Runs</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($usageSummary['agent_runs_count']) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($usageSummary['active_agent_runs_count']) }} running</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($usageSummary['failed_agent_runs_count']) }} failed</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Latest request</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ optional($usageSummary['latest_request_at'])->diffForHumans() ?? 'n/a' }}</div>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                Open the request log to inspect payloads, outputs, and filters.
            </div>
        </div>

        @if($ragSummary !== null)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">RAG Summary</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Conversation retrieval turns, citations, and unanswered questions.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Retrieval turns</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($ragSummary['retrieval_turns_count']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cited turns</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($ragSummary['cited_turns_count']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Citations</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($ragSummary['citation_count']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">No-answer turns</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($ragSummary['no_answer_turns_count']) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">No-answer rate</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($ragSummary['no_answer_rate'], 1) }}%</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($ragSummary['average_citations_per_turn'], 1) }} citations per cited turn</div>
                    </div>
                </div>
            </div>
        @endif

        @if($operationsSummary !== null)
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tool-call success rate</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($operationsSummary['tool_call_success_rate'], 1) }}%</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($operationsSummary['tool_calls_count']) }} total, {{ number_format($operationsSummary['tool_call_failed_count']) }} failed</div>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Workflow success rate</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($operationsSummary['workflow_success_rate'], 1) }}%</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($operationsSummary['workflow_runs_count']) }} total, {{ number_format($operationsSummary['workflow_failed_count']) }} failed</div>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Failures</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest broken AI requests, tool calls, and agent runs.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Detail</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Occurred</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($recentFailures as $failure)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $failure['type'] }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ $failure['url'] }}" class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                        {{ $failure['title'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $failure['detail'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ optional($failure['occurred_at'])->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No recent failures found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Runtime Configuration</h2>
        </div>
        <div class="grid gap-4 p-6 md:grid-cols-3">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Writer enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['writer_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
                <div class="mt-1 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $settings['writer_model'] ?? 'n/a' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Chat enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['chat_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
                <div class="mt-1 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $settings['chat_model'] ?? 'n/a' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Image enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['image_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
                <div class="mt-1 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $settings['image_model'] ?? 'n/a' }}</div>
            </div>
        </div>
        <div class="grid gap-4 border-t border-gray-200 p-6 md:grid-cols-2 dark:border-gray-800">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Vision model</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ $settings['vision_model'] ?? 'n/a' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Effective provider chain</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ $settings['default_provider'] ?? 'n/a' }} -> {{ $settings['fallback_provider'] ?? 'n/a' }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Layer</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Versioned agent configurations that combine prompts, tools, permissions, model policy, budgets, and step limits.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Version</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tools</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Steps</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Runtime</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($agents as $agent)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $agent['name'] }}</div>
                                @if(! empty($agent['description']))
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $agent['description'] }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">v{{ $agent['version'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $agent['prompt_key'] ?? 'n/a' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ implode(', ', $agent['tools'] ?? []) ?: 'n/a' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ implode(', ', $agent['permissions'] ?? []) ?: 'n/a' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ data_get($agent, 'budgets.max_tokens', 'n/a') }} tokens</div>
                                <div>{{ data_get($agent, 'budgets.max_cost', 'n/a') }} max cost</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $agent['max_steps'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $agent['max_seconds'] }} sec</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ ($agent['is_enabled'] ?? false) ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ ($agent['is_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Providers</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Capabilities</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($providers as $provider)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $provider['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $provider['driver'] }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $provider['status'] === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                    {{ $provider['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                    {{ $provider['status'] !== 'ready' && $provider['status'] !== 'error' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}">
                                    {{ ucfirst($provider['status']) }}
                                </span>
                                @if(! $provider['enabled'])
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ implode(', ', $provider['resolved_capabilities'] ?: $provider['configured_capabilities']) ?: 'n/a' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
