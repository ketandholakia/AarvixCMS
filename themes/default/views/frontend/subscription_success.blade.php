@extends('layouts.app')

@section('title', 'Subscription Successful')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-16 sm:py-24 h-[calc(100vh-80px)] flex items-center justify-center">
    <div class="mx-auto max-w-md px-6 lg:px-8 text-center">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-4">Welcome to Premium!</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Thank you for subscribing. Your payment was successful and you now have full access to all premium content.</p>
        <a href="{{ route('home') }}" class="inline-flex justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Start Reading
        </a>
    </div>
</div>
@endsection
