@extends('layouts.admin')

@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Posts Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15M9 11l3 3L22 4"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['posts'] }}</p>
                </div>
            </div>
        </div>

        <!-- Pages Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pages</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pages'] }}</p>
                </div>
            </div>
        </div>

        <!-- Views Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-pink-50 dark:bg-pink-900/30 rounded-xl text-pink-600 dark:text-pink-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Page Views</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_views']) }}</p>
                </div>
            </div>
        </div>

        <!-- Subscribers Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl text-amber-600 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subscribers</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['subscribers'] }}</p>
                </div>
            </div>
        </div>

        <!-- Comments Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Comments</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pending_comments'] }}</p>
                </div>
            </div>
        </div>

        <!-- Users Stat -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl text-purple-600 dark:text-purple-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['users'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($aiStats !== null)
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">AI Usage</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Last 30 days of request volume, cost, and reliability.</p>
                </div>
                <a href="{{ route('admin.ai-requests.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    View all requests
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($aiStats['requests_count']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Success Rate</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($aiStats['success_rate'], 1) }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failures</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($aiStats['failure_count']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tokens</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($aiStats['total_tokens']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estimated Cost</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">${{ number_format((float) $aiStats['estimated_cost'], 4) }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden p-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Activity Trend</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daily requests and tokens from the usage aggregation table.</p>
                    </div>
                </div>
                <div class="relative mt-4 h-72 w-full">
                    <canvas id="aiUsageChart"></canvas>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top AI Features</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Feature</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requests</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tokens</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @forelse($aiFeatureBreakdown as $row)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->feature }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ number_format($row->requests_count) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ number_format($row->total_tokens) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">${{ number_format((float) $row->estimated_cost, 4) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No AI feature usage recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Provider Mix</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Provider</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requests</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tokens</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @forelse($aiProviderBreakdown as $row)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->provider }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ number_format($row->requests_count) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ number_format($row->total_tokens) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">${{ number_format((float) $row->estimated_cost, 4) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No AI provider usage recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent AI Requests</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Most recent requests across writers, chat, tools, and workflows.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Feature</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Provider</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tokens</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($recentAiRequests as $request)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $request->feature }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                            @if($request->status === 'succeeded') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                            @elseif(in_array($request->status, ['failed', 'timed_out', 'rate_limited', 'rejected'], true)) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                            @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300
                                            @endif">
                                            {{ str_replace('_', ' ', $request->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <div>{{ $request->provider }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $request->model }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ number_format($request->total_tokens ?? 0) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">${{ number_format((float) ($request->estimated_cost ?? 0), 4) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $request->user?->name ?? 'System' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ optional($request->completed_at)->diffForHumans() ?? 'Pending' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No AI requests have been recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Traffic Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">30-Day Traffic</h2>
            <div class="relative h-72 w-full">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- Top Posts -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Top Posts</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($topPosts as $post)
                    <div class="px-6 py-4">
                        <a href="{{ route('admin.posts.edit', $post->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block mb-1">
                            {{ Str::limit($post->title, 40) }}
                        </a>
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $post->comments_count }} comments</span>
                            <span>{{ $post->revisions_count }} revisions</span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                        No posts yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h2>
            <a href="#" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View all &rarr;</a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($recentActivity as $activity)
                <div class="px-6 py-4 flex items-start gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                    <div class="mt-1">
                        @if($activity->action === 'created')
                            <span class="inline-block p-1.5 bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </span>
                        @elseif($activity->action === 'updated')
                            <span class="inline-block p-1.5 bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </span>
                        @elseif($activity->action === 'deleted')
                            <span class="inline-block p-1.5 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </span>
                        @else
                            <span class="inline-block p-1.5 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-900 dark:text-gray-200">
                            <span class="font-medium">{{ $activity->user->name ?? 'System' }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $activity->action }}</span>
                            <span class="font-medium">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" title="{{ $activity->created_at }}">
                            {{ $activity->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No recent activity found.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const isDark = document.documentElement.classList.contains('dark');
        
        const textColor = isDark ? '#9ca3af' : '#6b7280';
        const gridColor = isDark ? '#374151' : '#f3f4f6';
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartDates) !!},
                datasets: [{
                    label: 'Page Views',
                    data: {!! json_encode($chartViews) !!},
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDark ? '#1f2937' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#111827',
                        bodyColor: isDark ? '#d1d5db' : '#4b5563',
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: textColor,
                            maxTicksLimit: 7,
                            maxRotation: 0,
                        }
                    },
                    y: {
                        grid: {
                            color: gridColor,
                            borderDash: [5, 5],
                        },
                        ticks: {
                            color: textColor,
                            beginAtZero: true,
                            precision: 0,
                        },
                        border: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        @if($aiStats !== null)
        const aiCtx = document.getElementById('aiUsageChart').getContext('2d');

        new Chart(aiCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($aiChartDates) !!},
                datasets: [
                    {
                        label: 'Requests',
                        data: {!! json_encode($aiChartRequests) !!},
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                    },
                    {
                        label: 'Tokens',
                        data: {!! json_encode($aiChartTokens) !!},
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDark ? '#1f2937' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#111827',
                        bodyColor: isDark ? '#d1d5db' : '#4b5563',
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: true,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: textColor,
                            maxTicksLimit: 7,
                            maxRotation: 0,
                        }
                    },
                    y: {
                        grid: {
                            color: gridColor,
                            borderDash: [5, 5],
                        },
                        ticks: {
                            color: textColor,
                            beginAtZero: true,
                            precision: 0,
                        },
                        border: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
        @endif
    });
</script>
@endsection
