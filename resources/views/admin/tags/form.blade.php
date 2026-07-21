@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Tag' : 'Create Tag')

@section('content')
<div class="max-w-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tag Details</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage tag properties. The slug will be auto-generated if left blank.</p>
    </div>

    <form action="{{ $record->exists ? route('admin.tags.update', $record->id) : route('admin.tags.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="px-6 py-6 space-y-6">
            <x-admin.form.input 
                name="name" 
                label="Name" 
                :value="$record->name" 
                required="true" 
            />

            <x-admin.form.input 
                name="slug" 
                label="Slug" 
                :value="$record->slug" 
                help="Optional. Leave blank to auto-generate from name." 
            />
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.tags.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Tag' : 'Create Tag' }}
            </button>
        </div>
    </form>
</div>
@endsection
