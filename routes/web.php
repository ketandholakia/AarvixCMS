<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\FrontendController;
use App\Http\Middleware\PageCacheMiddleware;

Route::middleware([PageCacheMiddleware::class])->group(function() {
    Route::post('comments', [\App\Http\Controllers\Frontend\CommentController::class, 'store'])->name('comments.store');
    Route::post('newsletter/subscribe', [\App\Http\Controllers\Frontend\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('/category/{category_slug}', [FrontendController::class, 'index'])->name('category.show');
    Route::get('/tag/{tag_slug}', [FrontendController::class, 'index'])->name('tag.show');
    Route::get('/blog/{slug}', [FrontendController::class, 'showPost'])->name('post.show');
    Route::get('/forms/{slug}', [App\Http\Controllers\FrontendFormController::class, 'show'])->name('forms.show');
    Route::post('/forms/{slug}', [App\Http\Controllers\FrontendFormController::class, 'submit'])->name('forms.submit')->middleware('throttle:5,1');
    Route::get('/pricing', [\App\Http\Controllers\Frontend\SubscriptionController::class, 'pricing'])->name('pricing');
    Route::post('/pricing/checkout', [\App\Http\Controllers\Frontend\SubscriptionController::class, 'checkout'])->name('subscription.checkout')->middleware('auth');
    Route::get('/pricing/success', [\App\Http\Controllers\Frontend\SubscriptionController::class, 'success'])->name('subscription.success')->middleware('auth');
    Route::get('/pricing/cancel', [\App\Http\Controllers\Frontend\SubscriptionController::class, 'cancel'])->name('subscription.cancel')->middleware('auth');

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

        Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class)->except(['show']);
        Route::get('menus/{menu}/builder', [\App\Http\Controllers\Admin\MenuController::class, 'builder'])->name('menus.builder');
        Route::post('menus/{menu}/items', [\App\Http\Controllers\Admin\MenuController::class, 'addItem'])->name('menus.items.store');
        Route::put('menus/{menu}/items/reorder', [\App\Http\Controllers\Admin\MenuController::class, 'reorder'])->name('menus.items.reorder');
        Route::delete('menus/items/{id}', [\App\Http\Controllers\Admin\MenuController::class, 'destroyItem'])->name('menus.items.destroy');

        Route::post('upload-image', [\App\Http\Controllers\Admin\ImageUploadController::class, 'store'])->name('upload.image');
        
        Route::get('api-tokens', [\App\Http\Controllers\Admin\ApiTokenController::class, 'index'])->name('api_tokens.index');
        Route::post('api-tokens', [\App\Http\Controllers\Admin\ApiTokenController::class, 'store'])->name('api_tokens.store');
        Route::delete('api-tokens/{id}', [\App\Http\Controllers\Admin\ApiTokenController::class, 'destroy'])->name('api_tokens.destroy');
        // Revisions
        Route::get('revisions/{type}/{id}', [\App\Http\Controllers\Admin\RevisionController::class, 'index'])->name('revisions.index');
        Route::get('revisions/{revision}', [\App\Http\Controllers\Admin\RevisionController::class, 'show'])->name('revisions.show');
        Route::post('revisions/{revision}/restore', [\App\Http\Controllers\Admin\RevisionController::class, 'restore'])->name('revisions.restore');

        // Webhooks
        Route::resource('webhooks', \App\Http\Controllers\Admin\WebhookController::class)->except(['show']);

        // Comments
        Route::get('comments', [\App\Http\Controllers\Admin\CommentController::class, 'index'])->name('comments.index');
        Route::patch('comments/{comment}/status', [\App\Http\Controllers\Admin\CommentController::class, 'updateStatus'])->name('comments.status');
        Route::delete('comments/{comment}', [\App\Http\Controllers\Admin\CommentController::class, 'destroy'])->name('comments.destroy');

        // Subscribers
        Route::get('subscribers/export', [\App\Http\Controllers\Admin\SubscriberController::class, 'export'])->name('subscribers.export');
        Route::resource('subscribers', \App\Http\Controllers\Admin\SubscriberController::class)->except(['show']);
        
        // Subscriptions (Stripe Cashier)
        Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
        
        // Roles & Permissions
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->except(['show']);
        
        // Plugins
        Route::get('plugins', [\App\Http\Controllers\Admin\PluginController::class, 'index'])->name('plugins.index');
        Route::post('plugins/{id}/toggle', [\App\Http\Controllers\Admin\PluginController::class, 'toggle'])->name('plugins.toggle');
        
        // Themes
        Route::get('themes', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('themes.index');
        Route::post('themes/activate', [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('themes.activate');
    });

// Stripe Webhook (Cashier handles CSRF internally, but we need to exclude it from VerifyCsrfToken if we had it, but Laravel 11 automatically excludes it if we use the cashier method)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook']);
