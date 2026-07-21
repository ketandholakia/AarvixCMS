@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Page' : 'Create Page')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Page Content</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Design your static page.</p>
        </div>
        @if($record->exists)
            <a href="#" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline" target="_blank">View on Site &nearr;</a>
        @endif
    </div>

    <form action="{{ $record->exists ? route('admin.pages.update', $record->id) : route('admin.pages.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="flex flex-col lg:flex-row">
            <!-- Main Content Area -->
            <div class="flex-1 p-6 space-y-6 lg:border-r border-gray-200 dark:border-gray-800">
                <x-admin.form.input 
                    name="title" 
                    label="Page Title" 
                    :value="$record->title" 
                    required="true" 
                    class="text-lg font-medium"
                />

                <x-admin.form.input 
                    name="slug" 
                    label="Slug (URL)" 
                    :value="$record->slug" 
                    help="Optional. Leave blank to auto-generate from title." 
                />
                
                <x-admin.form.textarea 
                    name="body" 
                    label="Page Body" 
                    :value="$record->body" 
                    rows="20"
                    class="rich-editor"
                />
            </div>

            <!-- Sidebar Sidebar -->
            <div class="w-full lg:w-80 p-6 space-y-6 bg-gray-50/30 dark:bg-gray-900/30">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Publishing</h4>
                    
                    <x-admin.form.select 
                        name="status" 
                        label="Status" 
                        :value="$record->status ?? 'draft'"
                        :options="['draft' => 'Draft', 'published' => 'Published']"
                    />

                    <x-admin.form.select 
                        name="template" 
                        label="Page Template" 
                        :value="$record->template ?? 'default'" 
                        :options="['default' => 'Default (Centered)', 'full-width' => 'Full Width', 'sidebar' => 'With Sidebar', 'landing' => 'Landing (No Header)']"
                    />

                    <x-admin.form.input 
                        name="published_at" 
                        label="Publish Date" 
                        type="datetime-local"
                        :value="optional($record->published_at)->format('Y-m-d\TH:i')"
                    />
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Appearance</h4>
                    
                    <x-admin.form.select 
                        name="template" 
                        label="Template" 
                        :value="$record->template ?? 'default'"
                        :options="['default' => 'Default Layout', 'full-width' => 'Full Width', 'landing' => 'Landing Page', 'sidebar' => 'With Sidebar']"
                    />
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.pages.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Page' : 'Save Page' }}
            </button>
        </div>
    </form>
</div>
@endsection
