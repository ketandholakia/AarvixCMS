@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Subscriber' : 'Add Subscriber')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden max-w-2xl">
    <form action="{{ $record->exists ? route('admin.subscribers.update', $record->id) : route('admin.subscribers.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-admin.form.input 
                    name="first_name" 
                    label="First Name" 
                    :value="$record->first_name" 
                />
                
                <x-admin.form.input 
                    name="last_name" 
                    label="Last Name" 
                    :value="$record->last_name" 
                />
            </div>

            <x-admin.form.input 
                name="email" 
                type="email"
                label="Email Address" 
                :value="$record->email" 
                required="true" 
            />

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="subscribed" {{ old('status', $record->status) === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                    <option value="pending" {{ old('status', $record->status) === 'pending' ? 'selected' : '' }}>Pending (Awaiting Double Opt-in)</option>
                    <option value="unsubscribed" {{ old('status', $record->status) === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3">
            <a href="{{ route('admin.subscribers.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Subscriber' : 'Add Subscriber' }}
            </button>
        </div>
    </form>
</div>
@endsection
