@extends('layouts.admin')

@section('header', 'Revision Diff: ' . $record->title)

@section('content')
<div class="mb-6 flex justify-between items-center">
    @php
        $type = strtolower(class_basename($record));
    @endphp
    <a href="{{ route('admin.revisions.index', ['type' => $type, 'id' => $record->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Revisions</a>
    <form action="{{ route('admin.revisions.restore', $revision->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to restore this version?');">
        @csrf
        <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">Restore This Version</button>
    </form>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Revision by {{ $revision->user->name ?? 'System' }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Recorded on {{ $revision->created_at->format('M d, Y H:i:s') }}</p>
        </div>
        <div>
            <span class="px-3 py-1 text-sm rounded-full font-medium 
                {{ $revision->event === 'created' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                {{ $revision->event === 'updated' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                {{ $revision->event === 'deleted' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
            ">
                {{ ucfirst($revision->event) }}
            </span>
        </div>
    </div>

    <div class="p-6">
        @if(empty($keys))
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No attribute differences recorded for this revision.</p>
        @else
            <div class="space-y-8">
                @foreach($keys as $key)
                    @php
                        $oldVal = array_key_exists($key, $before) ? $before[$key] : null;
                        $newVal = array_key_exists($key, $after) ? $after[$key] : null;
                        
                        // Stringify arrays/objects for display
                        if(is_array($oldVal) || is_object($oldVal)) $oldVal = json_encode($oldVal, JSON_PRETTY_PRINT);
                        if(is_array($newVal) || is_object($newVal)) $newVal = json_encode($newVal, JSON_PRETTY_PRINT);
                    @endphp
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2 uppercase text-xs tracking-wider">{{ $key }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Old Value -->
                            <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-900/30 rounded-xl overflow-hidden">
                                <div class="px-4 py-2 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400 text-xs font-semibold border-b border-red-200 dark:border-red-900/30">
                                    Before
                                </div>
                                <div class="p-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono overflow-x-auto">{{ $oldVal ?? '— (null/empty) —' }}</div>
                            </div>
                            
                            <!-- New Value -->
                            <div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-900/30 rounded-xl overflow-hidden">
                                <div class="px-4 py-2 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400 text-xs font-semibold border-b border-green-200 dark:border-green-900/30">
                                    After
                                </div>
                                <div class="p-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono overflow-x-auto">{{ $newVal ?? '— (null/empty) —' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
