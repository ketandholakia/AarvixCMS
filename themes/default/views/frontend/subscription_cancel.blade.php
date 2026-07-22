@extends('layouts.app')

@section('title', 'Subscription Cancelled')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-16 sm:py-24 h-[calc(100vh-80px)] flex items-center justify-center">
    <div class="mx-auto max-w-md px-6 lg:px-8 text-center">
        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-4">Checkout Cancelled</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Your checkout process was cancelled and you haven't been charged. Feel free to try again when you are ready!</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('pricing') }}" class="inline-flex justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                View Plans Again
            </a>
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-md bg-white dark:bg-gray-800 px-6 py-3 text-base font-medium text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                Return Home
            </a>
        </div>
    </div>
</div>
@endsection
