@extends('layouts.admin')

@section('header', 'AI Agent Runs')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Runs</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Inspect persisted agent launches, step counts, halts, and failures.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ai-agent-runs.index', array_merge($filters, ['format' => 'csv'])) }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Export CSV
                </a>
                <a href="{{ route('admin.ai.diagnostics') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    AI diagnostics
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.ai-agent-runs.index') }}" class="mt-6 grid gap-3 md:grid-cols-3">
            <input type="text" name="agent_key" value="{{ $filters['agent_key'] }}" placeholder="Agent key" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <input type="text" name="status" value="{{ $filters['status'] }}" placeholder="Status" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total runs</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_runs']) }}</div>
        </div>
        <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-900/40 dark:bg-green-900/20">
            <div class="text-sm text-green-700 dark:text-green-300">Succeeded</div>
            <div class="mt-2 text-3xl font-bold text-green-900 dark:text-green-100">{{ number_format($summary['succeeded_count']) }}</div>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-900/40 dark:bg-blue-900/20">
            <div class="text-sm text-blue-700 dark:text-blue-300">Running</div>
            <div class="mt-2 text-3xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($summary['running_count']) }}</div>
        </div>
        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm dark:border-yellow-900/40 dark:bg-yellow-900/20">
            <div class="text-sm text-yellow-700 dark:text-yellow-300">Approval required</div>
            <div class="mt-2 text-3xl font-bold text-yellow-900 dark:text-yellow-100">{{ number_format($summary['approval_required_count']) }}</div>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm dark:border-red-900/40 dark:bg-red-900/20">
            <div class="text-sm text-red-700 dark:text-red-300">Failed</div>
            <div class="mt-2 text-3xl font-bold text-red-900 dark:text-red-100">{{ number_format($summary['failed_count']) }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Steps</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($runs as $run)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $run->agent_name }}</div>
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $run->agent_key }} v{{ $run->agent_version }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $run->status === 'succeeded' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }} {{ $run->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }} {{ $run->status === 'approval_required' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }} {{ $run->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $run->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $run->steps_completed }} / {{ $run->steps_planned }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $run->actor?->name ?? $run->actor?->email ?? 'n/a' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ optional($run->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.ai-agent-runs.show', $run) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No agent runs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $runs->links() }}
    </div>
</div>
@endsection
