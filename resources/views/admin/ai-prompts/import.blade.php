@extends('layouts.admin')

@section('header', 'Import AI Prompt')

@section('content')
<div class="max-w-5xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Import Prompt JSON</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Paste the JSON exported from a prompt detail page. The import creates the prompt and all versions.</p>
    </div>

    @if($errors->any())
        <div class="mx-6 mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-900/40 dark:bg-red-900/20">
            <div class="text-sm font-semibold text-red-800 dark:text-red-200">Fix the highlighted fields before importing.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.ai-prompts.import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-0">
        @csrf
        <div class="p-6">
            <div class="space-y-4">
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/40 dark:bg-indigo-900/20">
                    <label for="payload_file" class="block text-sm font-medium text-indigo-800 dark:text-indigo-200">Upload JSON file</label>
                    <input type="file" name="payload_file" id="payload_file" accept=".json,application/json" class="mt-2 block w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <p class="mt-2 text-sm text-indigo-700 dark:text-indigo-300">Choose an exported prompt JSON file. If provided, it takes precedence over pasted JSON.</p>
                    @error('payload_file')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <x-admin.form.textarea name="payload_json" label="Prompt JSON" :value="old('payload_json', '')" rows="22" help="Paste the JSON downloaded from the export action if you are not uploading a file." />
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.ai-prompts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                Import Prompt
            </button>
        </div>
    </form>
</div>
@endsection
