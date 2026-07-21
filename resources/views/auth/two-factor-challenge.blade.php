@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')

@section('content')
    <div x-data="{ recovery: false }">
        <h2 class="text-2xl font-semibold text-white mb-4">Two-Factor Authentication</h2>
        
        <div class="mb-6 text-sm text-gray-400 leading-relaxed" x-show="! recovery">
            Please confirm access to your account by entering the authentication code provided by your authenticator application.
        </div>

        <div class="mb-6 text-sm text-gray-400 leading-relaxed" x-show="recovery" style="display: none;">
            Please confirm access to your account by entering one of your emergency recovery codes.
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <!-- Auth Code -->
            <div x-show="! recovery">
                <label for="code" class="block text-sm font-medium text-gray-300 mb-1.5">Authentication Code</label>
                <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code"
                    class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner"
                    placeholder="123456">
                @error('code')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Recovery Code -->
            <div x-show="recovery" style="display: none;">
                <label for="recovery_code" class="block text-sm font-medium text-gray-300 mb-1.5">Recovery Code</label>
                <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                    class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner"
                    placeholder="xxxx-xxxx-xxxx-xxxx">
                @error('recovery_code')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors"
                        x-show="! recovery"
                        x-on:click="
                            recovery = true;
                            $nextTick(() => { $refs.recovery_code.focus() })
                        ">
                    Use a recovery code
                </button>

                <button type="button" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors"
                        x-show="recovery" style="display: none;"
                        x-on:click="
                            recovery = false;
                            $nextTick(() => { $refs.code.focus() })
                        ">
                    Use an authentication code
                </button>

                <button type="submit" 
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 transition-colors">
                    Log in
                </button>
            </div>
        </form>
    </div>
@endsection
