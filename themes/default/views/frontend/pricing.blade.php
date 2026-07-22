@extends('layouts.app')

@section('title', 'Premium Subscription')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
            <h2 class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400">Pricing</h2>
            <p class="mt-2 text-balance text-5xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-6xl">Unlock Premium Content</p>
        </div>
        <p class="mx-auto mt-6 max-w-2xl text-pretty text-center text-lg font-medium text-gray-600 dark:text-gray-400">Choose a subscription plan to get unlimited access to all our exclusive articles, tutorials, and community features.</p>
        
        <div class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:gap-x-8 lg:gap-y-0">
            <!-- Monthly Plan -->
            <div class="rounded-3xl p-8 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-800/50 flex flex-col justify-between">
                <div>
                    <h3 id="tier-monthly" class="text-lg/8 font-semibold text-gray-900 dark:text-white">Monthly</h3>
                    <p class="mt-4 text-sm/6 text-gray-600 dark:text-gray-400">Perfect for trying out our premium content.</p>
                    <p class="mt-6 flex items-baseline gap-x-1">
                        <span class="text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">$9</span>
                        <span class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400">/month</span>
                    </p>
                    <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 dark:text-gray-400">
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Full access to all premium posts
                        </li>
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Ad-free experience
                        </li>
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Cancel anytime
                        </li>
                    </ul>
                </div>
                <form action="{{ route('subscription.checkout') }}" method="POST" class="mt-8">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ env('STRIPE_MONTHLY_PRICE_ID', 'price_monthly_mock') }}">
                    <button type="submit" class="block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Subscribe Monthly</button>
                </form>
            </div>

            <!-- Yearly Plan -->
            <div class="rounded-3xl p-8 ring-2 ring-indigo-600 dark:ring-indigo-500 bg-white dark:bg-gray-800/50 flex flex-col justify-between relative shadow-xl">
                <div class="absolute -top-4 right-8 bg-indigo-600 px-3 py-1 text-xs font-semibold text-white rounded-full">Most Popular</div>
                <div>
                    <h3 id="tier-yearly" class="text-lg/8 font-semibold text-indigo-600 dark:text-indigo-400">Yearly</h3>
                    <p class="mt-4 text-sm/6 text-gray-600 dark:text-gray-400">Save 20% by paying annually.</p>
                    <p class="mt-6 flex items-baseline gap-x-1">
                        <span class="text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">$89</span>
                        <span class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400">/year</span>
                    </p>
                    <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 dark:text-gray-400">
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Full access to all premium posts
                        </li>
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Ad-free experience
                        </li>
                        <li class="flex gap-x-3">
                            <svg class="h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            Priority email support
                        </li>
                    </ul>
                </div>
                <form action="{{ route('subscription.checkout') }}" method="POST" class="mt-8">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ env('STRIPE_YEARLY_PRICE_ID', 'price_yearly_mock') }}">
                    <button type="submit" class="block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Subscribe Yearly</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
