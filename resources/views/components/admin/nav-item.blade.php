@props(['active' => false])

@php
$classes = $active
            ? 'flex items-center gap-3 px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-xl dark:bg-indigo-900/40 dark:text-indigo-300 transition-colors'
            : 'flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if (isset($icon))
        <div class="{{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' }}">
            {{ $icon }}
        </div>
    @endif
    {{ $slot }}
</a>
