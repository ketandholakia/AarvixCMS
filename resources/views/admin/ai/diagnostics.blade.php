@extends('layouts.admin')

@section('header', 'AI Diagnostics')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

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

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Runtime Configuration</h2>
        </div>
        <div class="grid gap-4 p-6 md:grid-cols-3">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Writer enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['writer_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Chat enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['chat_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Image enabled</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ ($settings['image_enabled'] ?? false) ? 'Yes' : 'No' }}</div>
            </div>
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
