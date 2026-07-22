@extends('layouts.admin')

@section('header', 'Hello World Plugin')

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden p-8 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 mb-6">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Hello from the Plugin System!</h2>
    <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        This page was loaded dynamically from the <code>plugins/HelloWorld</code> directory. 
        It demonstrates how you can register custom routes, load views, and inject hooks into the core application without touching any core files!
    </p>
</div>
@endsection
