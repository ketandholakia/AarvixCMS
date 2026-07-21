<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\FrontendController;
use App\Http\Middleware\PageCacheMiddleware;

Route::middleware([PageCacheMiddleware::class])->group(function() {
    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('/category/{category_slug}', [FrontendController::class, 'index'])->name('category.show');
    Route::get('/tag/{tag_slug}', [FrontendController::class, 'index'])->name('tag.show');
    Route::get('/blog/{slug}', [FrontendController::class, 'showPost'])->name('post.show');
    Route::get('/forms/{slug}', [App\Http\Controllers\FrontendFormController::class, 'show'])->name('forms.show');
    Route::post('/forms/{slug}', [App\Http\Controllers\FrontendFormController::class, 'submit'])->name('forms.submit')->middleware('throttle:5,1');
    Route::get('/{slug}', [FrontendController::class, 'showPage'])->name('page.show');
});

Route::middleware(['auth', \App\Http\Middleware\AuthorizeAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class);
        Route::resource('tags', \App\Http\Controllers\Admin\TagController::class);
        Route::resource('posts', \App\Http\Controllers\Admin\PostController::class);
        Route::resource('pages', \App\Http\Controllers\Admin\PageController::class);

        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
        
        Route::resource('forms', \App\Http\Controllers\Admin\FormController::class);
        Route::resource('form_submissions', \App\Http\Controllers\Admin\FormSubmissionController::class)->only(['index', 'show', 'destroy']);

        Route::resource('media', \App\Http\Controllers\Admin\MediaController::class)->only(['index', 'store', 'destroy']);
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['show']);

        Route::post('upload-image', [\App\Http\Controllers\Admin\ImageUploadController::class, 'store'])->name('upload.image');
    });
