@props(['location', 'class' => ''])

@php
    $menu = \Illuminate\Support\Facades\Cache::rememberForever("menu:{$location}", function () use ($location) {
        return \App\Models\Menu::with(['rootItems.children.linkable', 'rootItems.linkable'])
            ->where('location', $location)
            ->first();
    });
@endphp

@if($menu && $menu->rootItems->count() > 0)
    <div class="{{ $class }}">
        @foreach($menu->rootItems as $item)
            @if($item->children->count() > 0)
                <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors">
                        {{ $item->title }}
                        <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" 
                         x-transition.opacity
                         class="absolute left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
                         style="display: none;">
                        @foreach($item->children as $child)
                            <a href="{{ $child->resolved_url }}" 
                               target="{{ $child->target }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors">
                                {{ $child->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ $item->resolved_url }}" 
                   target="{{ $item->target }}"
                   class="text-sm font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors">
                    {{ $item->title }}
                </a>
            @endif
        @endforeach
    </div>
@endif
