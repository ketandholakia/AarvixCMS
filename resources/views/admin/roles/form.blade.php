@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Role' : 'Create Role')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden max-w-3xl">
    <form action="{{ $record->exists ? route('admin.roles.update', $record->id) : route('admin.roles.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="p-6 space-y-8">
            <x-admin.form.input 
                name="name" 
                label="Role Name" 
                :value="$record->name" 
                required="true"
                help="e.g. Editor, Author, Moderator" 
            />

            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Assign Permissions</h4>
                
                @php
                    $allPermissions = \App\Models\Permission::orderBy('name')->get();
                    $rolePermissions = $record->exists ? $record->permissions->pluck('id')->toArray() : [];
                    
                    // Group permissions by prefix if possible
                    $grouped = [];
                    foreach($allPermissions as $perm) {
                        $parts = explode('_', $perm->name);
                        $group = count($parts) > 1 ? ucfirst($parts[1]) : 'General';
                        $grouped[$group][] = $perm;
                    }
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($grouped as $groupName => $perms)
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-3">{{ $groupName }}</h5>
                            <div class="space-y-2">
                                @foreach($perms as $perm)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                               {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Role' : 'Create Role' }}
            </button>
        </div>
    </form>
</div>
@endsection
