@extends('layouts.admin')

@section('header', 'AI Tool Call')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tool call</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $call->tool?->name ?? 'Unknown tool' }}</h2>
            <div class="mt-2 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $call->call_uuid }}</div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.ai-tool-calls.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $call->status === 'succeeded' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }} {{ $call->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }} {{ in_array($call->status, ['pending', 'awaiting_approval'], true) ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }} {{ $call->status === 'rejected' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $call->status)) }}
                    </span>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $call->approval_state === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }} {{ $call->approval_state === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }} {{ $call->approval_state === 'rejected' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : '' }} {{ $call->approval_state === 'not_required' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $call->approval_state)) }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tool key</div>
                        <div class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $call->tool?->key ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $call->actor?->name ?? $call->actor?->email ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Source</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $call->source_type ?? 'n/a' }} @if($call->source_id) #{{ $call->source_id }}@endif</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Request UUID</div>
                        <div class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $call->request_uuid ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Started</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ optional($call->started_at)->format('Y-m-d H:i') ?? 'n/a' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed</div>
                        <div class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ optional($call->completed_at)->format('Y-m-d H:i') ?? 'n/a' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Input Payload</h3>
                </div>
                <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($call->input_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Result Summary</h3>
                @if($call->result_summary)
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($call->result_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No result has been stored yet.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Approval</h3>

                @if(in_array($call->status, ['awaiting_approval', 'pending'], true))
                    <div class="mt-4 space-y-3">
                        <form action="{{ route('admin.ai-tool-calls.approve', $call) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Approve and Execute</button>
                        </form>

                        <form action="{{ route('admin.ai-tool-calls.reject', $call) }}" method="POST" class="space-y-3">
                            @csrf
                            <textarea name="reason" rows="3" placeholder="Optional rejection reason" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                            <button type="submit" class="w-full rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Reject</button>
                        </form>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">This tool call is not awaiting approval.</p>
                    @if($call->approvedBy)
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Approved by {{ $call->approvedBy->name ?? $call->approvedBy->email }}</p>
                    @endif
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Error</h3>
                @if($call->error_class || $call->error_message)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Class</dt>
                            <dd class="mt-1 font-mono text-gray-800 dark:text-gray-200">{{ $call->error_class ?? 'n/a' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</dt>
                            <dd class="mt-1 text-gray-800 dark:text-gray-200">{{ $call->error_message ?? 'n/a' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No error recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
