@extends('layouts.admin')

@section('header', 'AI Agent Run')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Agent run</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $run->agent_name }}</h2>
            <div class="mt-2 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $run->run_uuid }}</div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.ai-agent-runs.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $run->status === 'succeeded' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }} {{ $run->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }} {{ $run->status === 'approval_required' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }} {{ $run->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $run->status)) }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agent key</div>
                        <div class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $run->agent_key }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Version</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">v{{ $run->agent_version }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $run->actor?->name ?? $run->actor?->email ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt key</div>
                        <div class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $run->prompt_key ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Steps</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $run->steps_completed }} / {{ $run->steps_planned }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $run->estimated_tokens }} tokens, {{ $run->estimated_cost }} cost</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Started</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ optional($run->started_at)->format('Y-m-d H:i') ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ optional($run->completed_at)->format('Y-m-d H:i') ?? 'n/a' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Plan</h3>
                <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($run->plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Result</h3>
                @if($run->result)
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($run->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No run result has been stored yet.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Context</h3>
                <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($run->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Error</h3>
                @if($run->error_class || $run->error_message)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Class</dt>
                            <dd class="mt-1 font-mono text-gray-800 dark:text-gray-200">{{ $run->error_class ?? 'n/a' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</dt>
                            <dd class="mt-1 text-gray-800 dark:text-gray-200">{{ $run->error_message ?? 'n/a' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No error recorded.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Steps</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tool</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Approval</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Call</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($run->steps as $step)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $step->step_index }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $step->tool_key }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $step->estimated_tokens }} tokens / {{ $step->estimated_cost }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $step->status)) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $step->approval_state ? ucfirst(str_replace('_', ' ', $step->approval_state)) : 'n/a' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                @if($step->toolCall)
                                    <a href="{{ route('admin.ai-tool-calls.show', $step->toolCall) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">View tool call</a>
                                @else
                                    n/a
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
