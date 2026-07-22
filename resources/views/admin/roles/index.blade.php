@extends('layouts.admin')

@section('header', 'Roles')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">Manage user roles and permissions.</p>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
            Create Role
        </a>
    </div>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissions</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($records as $role)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $role->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-lg">
                        <div class="flex flex-wrap gap-1">
                            @foreach($role->permissions->take(5) as $perm)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    {{ $perm->name }}
                                </span>
                            @endforeach
                            @if($role->permissions->count() > 5)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    +{{ $role->permissions->count() - 5 }} more
                                </span>
                            @endif
                            @if($role->permissions->isEmpty())
                                <span class="text-xs italic text-gray-400">None</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</a>
                        @if($role->name !== 'Super Admin')
                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this role? Users with this role will lose these permissions.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        No roles found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($records->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
