@extends('layouts.admin')

@section('header', 'AI Request')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $request->feature }}</div>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $request->prompt_key ?? 'AI Request' }}</h2>
                <div class="mt-2 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $request->request_uuid }}</div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ai-requests.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Back to log
                </a>
                <a href="{{ route('admin.ai.diagnostics') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    AI diagnostics
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:col-span-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Summary</h3>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $request->provider }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $request->model }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Actor</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $request->user?->name ?? 'System' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Started</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ optional($request->started_at)->format('Y-m-d H:i:s') ?? 'n/a' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Completed</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ optional($request->completed_at)->format('Y-m-d H:i:s') ?? 'n/a' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Latency</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ number_format($request->latency_ms ?? 0) }} ms</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Tokens</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">
                        {{ number_format($request->total_tokens ?? 0) }} total
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($request->prompt_tokens ?? 0) }} prompt / {{ number_format($request->completion_tokens ?? 0) }} completion
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Estimated cost</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">${{ number_format((float) ($request->estimated_cost ?? 0), 4) }}</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Error</h3>
                @if($request->error_class || $request->error_message)
                    <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Class</div>
                            <div class="mt-1 font-mono">{{ $request->error_class ?? 'n/a' }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</div>
                            <div class="mt-1">{{ $request->error_message ?? 'n/a' }}</div>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No error recorded for this request.</p>
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Request Metadata</h3>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ json_encode($request->request_metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Response Metadata</h3>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ json_encode($request->response_metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Request Payload</h3>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ json_encode($request->request_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Response Payload</h3>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ json_encode($request->response_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scope</h3>
                <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ json_encode($request->scope ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
