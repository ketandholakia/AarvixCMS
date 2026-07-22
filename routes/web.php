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

    // Custom content type entries (must be before the /{slug} catch-all)
    Route::get('/{type_slug}/{slug}', [\App\Http\Controllers\EntryController::class, 'show'])
        ->name('entry.show')
        ->where('type_slug', '[a-z0-9\-]+')
        ->where('slug', '[a-z0-9\-]+');

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

        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index')->middleware('can:manage_settings');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update')->middleware('can:manage_settings');
        
        Route::resource('forms', \App\Http\Controllers\Admin\FormController::class);
        Route::resource('form_submissions', \App\Http\Controllers\Admin\FormSubmissionController::class)->only(['index', 'show', 'destroy']);

        Route::resource('media', \App\Http\Controllers\Admin\MediaController::class)->only(['index', 'store', 'destroy'])->middleware('can:manage_media');
        
        // Users is split per-verb for privilege escalation risk
        Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index')->middleware('can:view_users');
        Route::get('users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create')->middleware('can:create_users');
        Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store')->middleware('can:create_users');
        Route::get('users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit')->middleware('can:edit_users');
        Route::put('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update')->middleware('can:edit_users');
        Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete_users');

        Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class)->except(['show'])->middleware('can:manage_menus');
        Route::get('menus/{menu}/builder', [\App\Http\Controllers\Admin\MenuController::class, 'builder'])->name('menus.builder')->middleware('can:manage_menus');
        Route::post('menus/{menu}/items', [\App\Http\Controllers\Admin\MenuController::class, 'addItem'])->name('menus.items.store')->middleware('can:manage_menus');
        Route::put('menus/{menu}/items/reorder', [\App\Http\Controllers\Admin\MenuController::class, 'reorder'])->name('menus.items.reorder')->middleware('can:manage_menus');
        Route::delete('menus/items/{id}', [\App\Http\Controllers\Admin\MenuController::class, 'destroyItem'])->name('menus.items.destroy')->middleware('can:manage_menus');

        Route::post('upload-image', [\App\Http\Controllers\Admin\ImageUploadController::class, 'store'])->name('upload.image'); // Used by editorjs etc.
        
        Route::get('api-tokens', [\App\Http\Controllers\Admin\ApiTokenController::class, 'index'])->name('api_tokens.index')->middleware('can:manage_api_tokens');
        Route::post('api-tokens', [\App\Http\Controllers\Admin\ApiTokenController::class, 'store'])->name('api_tokens.store')->middleware('can:manage_api_tokens');
        Route::delete('api-tokens/{id}', [\App\Http\Controllers\Admin\ApiTokenController::class, 'destroy'])->name('api_tokens.destroy')->middleware('can:manage_api_tokens');
        
        // Revisions
        Route::get('revisions/{type}/{id}', [\App\Http\Controllers\Admin\RevisionController::class, 'index'])->name('revisions.index')->middleware('can:manage_revisions');
        Route::get('revisions/{revision}', [\App\Http\Controllers\Admin\RevisionController::class, 'show'])->name('revisions.show')->middleware('can:manage_revisions');
        Route::post('revisions/{revision}/restore', [\App\Http\Controllers\Admin\RevisionController::class, 'restore'])->name('revisions.restore')->middleware('can:manage_revisions');

        // Webhooks
        Route::resource('webhooks', \App\Http\Controllers\Admin\WebhookController::class)->except(['show'])->middleware('can:manage_webhooks');

        // Comments
        Route::get('comments', [\App\Http\Controllers\Admin\CommentController::class, 'index'])->name('comments.index')->middleware('can:manage_comments');
        Route::patch('comments/{comment}/status', [\App\Http\Controllers\Admin\CommentController::class, 'updateStatus'])->name('comments.status')->middleware('can:manage_comments');
        Route::delete('comments/{comment}', [\App\Http\Controllers\Admin\CommentController::class, 'destroy'])->name('comments.destroy')->middleware('can:manage_comments');

        // Subscribers
        Route::get('subscribers/export', [\App\Http\Controllers\Admin\SubscriberController::class, 'export'])->name('subscribers.export')->middleware('can:manage_subscribers');
        Route::resource('subscribers', \App\Http\Controllers\Admin\SubscriberController::class)->except(['show'])->middleware('can:manage_subscribers');
        
        // Subscriptions (Stripe Cashier)
        Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index')->middleware('can:view_subscriptions');
        
        // Roles & Permissions
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->except(['show']);
        
        // Plugins
        Route::get('plugins', [\App\Http\Controllers\Admin\PluginController::class, 'index'])->name('plugins.index')->middleware('can:manage_plugins');
        Route::post('plugins/{id}/toggle', [\App\Http\Controllers\Admin\PluginController::class, 'toggle'])->name('plugins.toggle')->middleware('can:manage_plugins');
        
        // Themes
        Route::get('themes', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('themes.index')->middleware('can:manage_themes');
        Route::post('themes/activate', [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('themes.activate')->middleware('can:manage_themes');

        // Content Types
        Route::get('content-types/{content_type}/field-builder', [\App\Http\Controllers\Admin\ContentTypeController::class, 'fieldBuilder'])->name('content-types.field-builder');
        Route::put('content-types/{content_type}/field-builder', [\App\Http\Controllers\Admin\ContentTypeController::class, 'saveSchema'])->name('content-types.save-schema');
        Route::resource('content-types', \App\Http\Controllers\Admin\ContentTypeController::class);

        // Custom Type Entries (parameterised by type slug)
        Route::prefix('entries/{type}')->name('entries.')->group(function () {
            Route::get('/',           [\App\Http\Controllers\Admin\EntryController::class, 'index'])  ->name('index');
            Route::get('/create',     [\App\Http\Controllers\Admin\EntryController::class, 'create']) ->name('create');
            Route::post('/',          [\App\Http\Controllers\Admin\EntryController::class, 'store'])  ->name('store');
            Route::get('/{entry}/edit',    [\App\Http\Controllers\Admin\EntryController::class, 'edit'])   ->name('edit');
            Route::put('/{entry}',         [\App\Http\Controllers\Admin\EntryController::class, 'update']) ->name('update');
            Route::delete('/{entry}',      [\App\Http\Controllers\Admin\EntryController::class, 'destroy'])->name('destroy');
        });
    });

// Stripe Webhook (Cashier handles CSRF internally, but we need to exclude it from VerifyCsrfToken if we had it, but Laravel 11 automatically excludes it if we use the cashier method)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook']);
