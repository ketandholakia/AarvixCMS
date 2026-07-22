<?php

namespace Plugins\HelloWorld\Providers;

use Illuminate\Support\ServiceProvider;
use App\Facades\Hook;

class HelloWorldServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'helloworld');

        Hook::addAction('admin_sidebar_menu', function () {
            echo '<a href="/admin/helloworld" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition-colors text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Hello World
            </a>';
        });
    }
}
