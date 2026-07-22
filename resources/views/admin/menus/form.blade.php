@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Menu' : 'Create Menu')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden max-w-3xl">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $record->exists ? 'Edit Menu Details' : 'Create New Menu' }}
        </h3>
    </div>

    <form action="{{ $record->exists ? route('admin.menus.update', $record->id) : route('admin.menus.store') }}" method="POST">
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

            <x-admin.form.input 
                name="name" 
                label="Menu Name" 
                :value="$record->name" 
                required="true"
                help="For internal reference (e.g. 'Main Navigation')" 
            />

            <x-admin.form.input 
                name="location" 
                label="Location Identifier" 
                :value="$record->location" 
                required="true"
                help="Used in the theme to fetch this menu (e.g. 'primary', 'footer_links')" 
            />
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.menus.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Menu' : 'Create Menu' }}
            </button>
        </div>
    </form>
</div>
@endsection
