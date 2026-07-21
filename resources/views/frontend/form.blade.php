@extends('layouts.app')

@section('title', $form->name)

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-16 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-10">
            <h1 class="font-heading text-4xl font-bold text-gray-900 dark:text-white">{{ $form->name }}</h1>
            @if($form->description)
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ $form->description }}</p>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-md">
                        <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('forms.submit', $form->slug) }}" method="POST" class="space-y-6">
                    @csrf
                    
                    @foreach($form->fields as $field)
                        <div>
                            @if($field['type'] === 'checkbox')
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="{{ $field['name'] }}" 
                                            name="{{ $field['name'] }}" 
                                            type="checkbox" 
                                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
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
                                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            {{ !empty($field['required']) ? 'required' : '' }}
                                        >{{ old($field['name']) }}</textarea>
                                    @else
                                        <input 
                                            type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" 
                                            id="{{ $field['name'] }}" 
                                            name="{{ $field['name'] }}" 
                                            value="{{ old($field['name']) }}"
                                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
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
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Submit Form
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
