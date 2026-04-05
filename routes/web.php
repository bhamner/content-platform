<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CreatorStripeController;
use App\Http\Controllers\ProductFileDownloadController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Volt::route('/', 'storefront.home')->name('home');

Volt::route('/c/{creatorProfile}/p/{product_slug}', 'storefront.product')->name('storefront.product');

Volt::route('/c/{creatorProfile}', 'storefront.creator-store')->name('storefront.creator');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/library', 'library.index')->name('library.index');

    Route::post('/checkout/{product}', CheckoutController::class)->name('checkout.store');

    Route::get('/library/files/{file}/download', ProductFileDownloadController::class)
        ->name('library.download');

    Route::prefix('creator')->name('creator.')->group(function () {
        Volt::route('/setup', 'creator.profile-setup')->name('setup');
        Volt::route('/products', 'creator.products-index')->name('products.index');
        Volt::route('/products/create', 'creator.product-form')->name('products.create');
        Volt::route('/products/{product}/edit', 'creator.product-form')->name('products.edit');

        Route::get('/stripe/connect', [CreatorStripeController::class, 'connect'])->name('stripe.connect');
        Route::get('/stripe/refresh', [CreatorStripeController::class, 'refresh'])->name('stripe.refresh');
        Route::get('/stripe/return', [CreatorStripeController::class, 'return'])->name('stripe.return');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
