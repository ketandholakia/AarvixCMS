@extends('layouts.admin')

@section('header', 'API Tokens')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Personal Access Tokens</h2>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('plainTextToken'))
        <div class="p-4 bg-indigo-50 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-xl border border-indigo-200 dark:border-indigo-800">
            <h4 class="font-bold mb-2">Save your new token!</h4>
            <p class="text-sm mb-4">Please copy this token now. For your security, it won't be shown again.</p>
            <div class="flex items-center gap-2">
                <code class="px-3 py-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 select-all font-mono text-sm break-all">
                    {{ session('plainTextToken') }}
                </code>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden p-6">
        <h3 class="font-medium text-gray-900 dark:text-white mb-4">Create New Token</h3>
        <form action="{{ route('admin.api_tokens.store') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <x-admin.form.input name="name" label="Token Name" required="true" placeholder="e.g. Mobile App" />
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abilities</label>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="abilities[]" value="api.read" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Read Content</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="abilities[]" value="api.write" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Create/Update/Delete Content</span>
                    </label>
                </div>
            </div>
            
            <div class="mt-2">
                <button type="submit" class="px-4 py-2 h-10 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
                    Create Token
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <h3 class="font-medium text-gray-900 dark:text-white">Active Tokens</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($tokens as $token)
                <div class="p-6 flex items-center justify-between hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $token->name }}</h4>
                        <div class="mt-1 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span>Created: {{ $token->created_at->format('M j, Y') }}</span>
                            @if($token->last_used_at)
                                <span>Last used: {{ $token->last_used_at->diffForHumans() }}</span>
                            @else
                                <span>Never used</span>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('admin.api_tokens.destroy', $token->id) }}" method="POST" onsubmit="return confirm('Revoke this token? Applications using it will lose access.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            Revoke
                        </button>
                    </form>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    You have no active API tokens.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
