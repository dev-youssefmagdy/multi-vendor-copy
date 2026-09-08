<?php

declare(strict_types=1);

/**
 * Public storefront REST API.
 *
 * Base URL: {APP_URL}/api/v1
 *
 * Every request must identify the tenant (store) via a bearer token, where
 * the token is the tenant's id:
 *
 *   Authorization: Bearer {tenant_id}
 *
 * See the "Storefront API" article under Tenant Help & Docs for the full
 * reference (resources/views/livewire/tenant/help/articles/api-reference.blade.php).
 */

use App\Http\Controllers\Tenant\AddressController;
use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\CatalogController;
use App\Http\Controllers\Tenant\CheckoutController;
use App\Http\Controllers\Tenant\CouponController;
use App\Http\Controllers\Tenant\FavoriteController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['tenant.api.token'])
    ->name('api.v1.')
    ->group(function () {
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

        // ── Public catalog browsing (no shopper login required) ──────────────
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories.index');
        Route::get('/categories/{slug}', [CatalogController::class, 'category'])->name('categories.show');
        Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
        Route::get('/products/{slug}', [CatalogController::class, 'product'])->name('products.show');
        Route::get('/products/{product}/reviews', [ReviewController::class, 'index'])->name('reviews.index');

        Route::middleware('auth:storefront')->group(function () {
            Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
            Route::get('/favorites/list', [FavoriteController::class, 'list'])->name('favorites.list');
            Route::get('/favorites/ids', [FavoriteController::class, 'ids'])->name('favorites.ids');

            Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

            Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
            Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
            Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
            Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
            Route::post('/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

            Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

            Route::get('/coupon', [CouponController::class, 'show'])->name('coupon.show');
            Route::post('/coupon/apply', [CouponController::class, 'apply'])->name('coupon.apply');
            Route::post('/coupon/remove', [CouponController::class, 'remove'])->name('coupon.remove');

            Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
            Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');

            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{uuid}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{uuid}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');
            Route::post('/orders/{uuid}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        });
    });
