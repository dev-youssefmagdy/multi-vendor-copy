<?php

declare(strict_types=1);

/**
 * Mobile app API. Base: {APP_URL}/api/mobile/v1  (see docs/mobile/01_BACKEND_CONTRACT.md).
 * Stateless: no session/cookies. Tenant = X-Tenant header; auth = hashed mobile token
 * looked up in that tenant's own database (cross-tenant tokens cannot match).
 */

use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\BootstrapController;
use App\Http\Controllers\Mobile\CatalogController;
use App\Http\Controllers\Mobile\HomeController;
use App\Http\Controllers\Mobile\PageController;
use App\Http\Controllers\Mobile\ResolveController;
use App\Http\Controllers\Tenant\AddressController;
use App\Http\Controllers\Tenant\FavoriteController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/resolve', ResolveController::class)->middleware('throttle:30,1')->name('resolve');

Route::middleware('mobile.tenant')->group(function () {
    Route::get('/bootstrap', BootstrapController::class)->name('bootstrap');
    Route::get('/home/sections/{key}', [HomeController::class, 'section'])->name('home.section');

    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/menus', [PageController::class, 'menus'])->name('menus');

    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{slug}', [CatalogController::class, 'category'])->name('categories.show');
    Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
    Route::get('/products/{slug}', [CatalogController::class, 'product'])->name('products.show');
    Route::get('/products/{product}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/search', [CatalogController::class, 'search'])->name('search');
    Route::get('/search/autocomplete', [CatalogController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('/lists/{list}', [CatalogController::class, 'lists'])->name('lists');

    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot');
    });

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::delete('/auth/account', [AuthController::class, 'deleteAccount'])->name('auth.account.delete');

        Route::get('/me', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::get('/favorites/list', [FavoriteController::class, 'list'])->name('favorites.list');
        Route::get('/favorites/ids', [FavoriteController::class, 'ids'])->name('favorites.ids');

        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/cancellation-reasons', [OrderController::class, 'cancellationReasons'])->name('orders.cancellation-reasons');
        Route::get('/orders/{uuid}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{uuid}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });
});
