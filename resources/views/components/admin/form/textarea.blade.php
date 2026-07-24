@props(['name', 'label' => '', 'value' => '', 'required' => false, 'help' => '', 'rows' => 5])

@php
    $hasError = $errors->has($name);
@endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    
    <textarea 
        name="{{ $name }}" 
        id="{{ $name }}" 
        rows="{{ $rows }}"
        {{ $required ? 'required' : '' }}
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-xl shadow-sm sm:text-sm transition-colors py-2 px-3 custom-scrollbar dark:bg-gray-900 dark:text-white focus:ring-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 ' . ($hasError ? 'border-red-300 focus:border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500')]) }}
    >{{ old($name, $value) }}</textarea>
    
    @error($name)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    @if($help && ! $hasError)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif
</div>
