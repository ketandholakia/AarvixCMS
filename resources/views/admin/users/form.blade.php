@extends('layouts.admin')

@section('header', $record->exists ? 'Edit User' : 'Create User')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $record->exists ? 'Edit ' . $record->name : 'Create New User' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $record->exists ? 'Update account details and role assignments.' : 'Add a new user and assign a role.' }}
        </p>
    </div>

    <form action="{{ $record->exists ? route('admin.users.update', $record->id) : route('admin.users.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="p-6 space-y-6">
            @if($errors->any())
                <div class="p-4 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-xl text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $record->name) }}" required
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email', $record->email) }}" required
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Password {{ $record->exists ? '(leave blank to keep current)' : '' }} @if(!$record->exists)<span class="text-red-500">*</span>@endif
                    </label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                           {{ !$record->exists ? 'required' : '' }}
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- Active toggle --}}
                <div class="flex items-center gap-3 mt-6">
                    <input id="is_active" type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Account is active <span class="text-xs text-gray-400">(inactive users cannot login)</span>
                    </label>
                </div>
            </div>

            {{-- Roles --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Assign Roles</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($roles as $role)
                        @php $selected = old('roles', $record->exists ? $record->roles->pluck('id')->toArray() : []); @endphp
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-600 transition-colors {{ in_array($role->id, $selected) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'bg-white dark:bg-gray-800' }}">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                   {{ in_array($role->id, $selected) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>
@endsection
