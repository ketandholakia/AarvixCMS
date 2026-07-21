@extends('layouts.admin')

@section('header', 'Global Settings')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Site Settings</h2>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="p-6 space-y-8">
                <!-- General Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">General Information</h3>
                    <div class="space-y-6">
                        <x-admin.form.input 
                            name="site_name" 
                            label="Site Name" 
                            :value="$settings['site_name'] ?? config('app.name')" 
                            required="true"
                            help="The primary name of your website, displayed in the header and title tags."
                        />
                        
                        <x-admin.form.textarea 
                            name="site_description" 
                            label="Site Description (SEO)" 
                            :value="$settings['site_description'] ?? ''"
                            rows="3"
                            help="A short summary of your site for search engines."
                        />
                    </div>
                </div>

                <!-- Social Links -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">Social Links</h3>
                    <div class="space-y-6">
                        <x-admin.form.input 
                            name="social_twitter" 
                            label="Twitter / X URL" 
                            type="url"
                            :value="$settings['social_twitter'] ?? ''" 
                        />
                        <x-admin.form.input 
                            name="social_github" 
                            label="GitHub URL" 
                            type="url"
                            :value="$settings['social_github'] ?? ''" 
                        />
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
