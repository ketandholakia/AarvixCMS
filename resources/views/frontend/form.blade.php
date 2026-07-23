@extends('layouts.app')

@section('title', $form->name)

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-16 min-h-screen">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h1 class="font-heading text-4xl font-bold text-gray-900 dark:text-white">{{ $form->name }}</h1>
            @if($form->description)
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ $form->description }}</p>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-800">
            <div class="p-8">
                @if(session('success'))
                    <div class="mb-6 rounded-md border-l-4 border-green-500 bg-green-50 p-4 dark:bg-green-900/30">
                        <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('forms.submit', $form->slug) }}" method="POST" class="space-y-6">
                    @csrf

                    @foreach($form->fields as $field)
                        <div>
                            @if($field['type'] === 'checkbox')
                                <div class="flex items-start">
                                    <div class="flex h-5 items-center">
                                        <input
                                            id="{{ $field['name'] }}"
                                            name="{{ $field['name'] }}"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            {{ old($field['name']) ? 'checked' : '' }}
                                            {{ !empty($field['required']) ? 'required' : '' }}
                                        >
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="{{ $field['name'] }}" class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $field['label'] }}
                                            @if(!empty($field['required']))
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @else
                                <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $field['label'] }}
                                    @if(!empty($field['required']))
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                <div class="mt-1">
                                    @if($field['type'] === 'textarea')
                                        <textarea
                                            id="{{ $field['name'] }}"
                                            name="{{ $field['name'] }}"
                                            rows="4"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            {{ !empty($field['required']) ? 'required' : '' }}
                                        >{{ old($field['name']) }}</textarea>
                                    @else
                                        <input
                                            type="{{ $field['type'] === 'email' ? 'email' : 'text' }}"
                                            id="{{ $field['name'] }}"
                                            name="{{ $field['name'] }}"
                                            value="{{ old($field['name']) }}"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            {{ !empty($field['required']) ? 'required' : '' }}
                                        >
                                    @endif
                                </div>
                            @endif

                            @error($field['name'])
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="pt-4">
                        <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Submit Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
