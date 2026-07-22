@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Webhook' : 'Create Webhook')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden max-w-3xl">
    <form action="{{ $record->exists ? route('admin.webhooks.update', $record->id) : route('admin.webhooks.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="p-6 space-y-6">
            <x-admin.form.input 
                name="name" 
                label="Webhook Name" 
                :value="$record->name" 
                required="true" 
                help="E.g. Netlify Build Trigger" 
            />

            <x-admin.form.input 
                name="url" 
                type="url"
                label="Payload URL" 
                :value="$record->url" 
                required="true" 
                help="The endpoint that will receive the POST request." 
            />

            <x-admin.form.input 
                name="secret" 
                label="Secret (Optional)" 
                :value="$record->secret" 
                help="Used to generate an HMAC SHA256 signature in the X-AarvixCMS-Signature header." 
            />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Events to Trigger On</label>
                <div class="flex flex-col gap-2">
                    @php
                        $selectedEvents = $record->events ?? ['*'];
                    @endphp
                    
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="events[]" value="*" {{ in_array('*', $selectedEvents) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">All Events (*)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="events[]" value="post.updated" {{ in_array('post.updated', $selectedEvents) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Post Updated (post.updated)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="events[]" value="post.deleted" {{ in_array('post.deleted', $selectedEvents) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Post Deleted (post.deleted)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="events[]" value="page.updated" {{ in_array('page.updated', $selectedEvents) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Page Updated (page.updated)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="events[]" value="page.deleted" {{ in_array('page.deleted', $selectedEvents) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Page Deleted (page.deleted)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.webhooks.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Webhook' : 'Save Webhook' }}
            </button>
        </div>
    </form>
</div>
@endsection
