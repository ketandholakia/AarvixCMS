<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\AuthorizeAdmin::class])
    ->prefix('admin/helloworld')
    ->group(function () {
        Route::get('/', function () {
            return view('helloworld::index');
        })->name('admin.helloworld.index');
    });
