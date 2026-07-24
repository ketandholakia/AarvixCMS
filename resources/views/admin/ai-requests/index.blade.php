@extends('layouts.admin')

@section('header', 'AI Requests')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Request Log</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Filter and inspect AI request activity across prompts, writer actions, chat, tools, and automation.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ai-requests.export', request()->only(['feature', 'status', 'provider'])) }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Export CSV
                </a>
                <a href="{{ route('admin.ai.diagnostics') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    AI diagnostics
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Dashboard
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.ai-requests.index') }}" class="mt-6 grid gap-3 md:grid-cols-4">
            <input type="text" name="feature" value="{{ $filters['feature'] }}" placeholder="Feature" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <input type="text" name="status" value="{{ $filters['status'] }}" placeholder="Status" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <input type="text" name="provider" value="{{ $filters['provider'] }}" placeholder="Provider" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Request</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tokens</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/50">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.ai-requests.show', $request) }}" class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">{{ $request->feature }}</a>
                                <div class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $request->prompt_key ?? 'n/a' }}</div>
                                <div class="mt-1 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $request->request_uuid }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    @if($request->status === 'succeeded') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @elseif(in_array($request->status, ['failed', 'timed_out', 'rate_limited', 'rejected', 'cancelled'], true)) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                    @elseif($request->status === 'running') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </span>
                                @if($request->error_class || $request->error_message)
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $request->error_class ?: $request->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ $request->provider }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $request->model }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ number_format($request->total_tokens ?? 0) }} total</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ number_format($request->prompt_tokens ?? 0) }} prompt / {{ number_format($request->completion_tokens ?? 0) }} completion</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">${{ number_format((float) ($request->estimated_cost ?? 0), 4) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $request->user?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.ai-requests.show', $request) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No AI requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $requests->links() }}
    </div>
</div>
@endsection
