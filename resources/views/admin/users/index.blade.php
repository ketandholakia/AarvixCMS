@extends('layouts.admin')

@section('header', 'Users')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manage Users</h2>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
            Create User
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-3">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by name or email..."
            class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
        <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium transition-colors">
            Search
        </button>
    </form>

    <x-admin.table :headers="['ID', 'Name', 'Email', 'Roles', 'Status', 'Joined']" :records="$records" actions="true">
        @forelse($records as $user)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $user->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <img class="w-8 h-8 rounded-full object-cover"
                             src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=4f46e5&background=e0e7ff"
                             alt="{{ $user->name }}">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                        @if($user->id === auth()->id())
                            <span class="px-1.5 py-0.5 text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded">You</span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                        @forelse($user->roles as $role)
                            <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-lg">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">—</span>
                        @endforelse
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($user->is_active)
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg">Active</span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg">Inactive</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-sm">
                    {{ $user->created_at->format('M j, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block"
                                  onsubmit="return confirm('Delete user {{ addslashes($user->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No users found.
                </td>
            </tr>
        @endforelse
    </x-admin.table>
</div>
@endsection
