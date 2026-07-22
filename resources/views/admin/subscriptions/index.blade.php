@extends('layouts.admin')

@section('header', 'Subscriptions')

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Subscribers</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 uppercase border-b border-gray-100 dark:border-gray-800">
                <tr>
                    <th class="px-6 py-4 font-medium">User</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Plan</th>
                    <th class="px-6 py-4 font-medium">Started</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($subscriptions as $sub)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $sub->name }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $sub->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($sub->stripe_status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                {{ ucfirst($sub->stripe_status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                        {{ $sub->type }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                        {{ \Carbon\Carbon::parse($sub->created_at)->format('M d, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        No subscriptions found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($subscriptions->hasPages())
    <div class="p-6 border-t border-gray-100 dark:border-gray-800">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
@endsection
