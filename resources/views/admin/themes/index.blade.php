@extends('layouts.admin')

@section('header', 'Themes')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Installed Themes</h3>
    <p class="text-sm text-gray-500">Manage the look and feel of your frontend.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($themes as $theme)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border {{ $theme['is_active'] ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-100 dark:border-gray-800' }} shadow-sm overflow-hidden flex flex-col">
            <div class="aspect-video bg-gray-100 dark:bg-gray-800 relative">
                @if(file_exists($theme['path'] . '/screenshot.png'))
                    <img src="{{ asset('themes/' . $theme['id'] . '/screenshot.png') }}" class="w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400">
                        <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                @endif
                
                @if($theme['is_active'])
                    <div class="absolute top-2 right-2 bg-indigo-500 text-white text-xs px-2 py-1 rounded-md font-medium shadow-sm">
                        Active
                    </div>
                @endif
            </div>
            
            <div class="p-5 flex-1 flex flex-col">
                <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $theme['name'] }}</h4>
                <p class="text-sm text-gray-500 mt-1 mb-4 flex-1">{{ $theme['description'] }}</p>
                
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                    <span>v{{ $theme['version'] }}</span>
                    <span>By {{ $theme['author'] }}</span>
                </div>
                
                @if(!$theme['is_active'])
                    <form action="{{ route('admin.themes.activate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="theme" value="{{ $theme['id'] }}">
                        <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Activate
                        </button>
                    </form>
                @else
                    <button disabled class="w-full py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 cursor-not-allowed">
                        Currently Active
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">No themes found. Create a theme in the <code>/themes</code> directory.</p>
        </div>
    @endforelse
</div>
@endsection
