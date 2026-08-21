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

use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['tenant.api.token'])
    ->name('api.v1.')
    ->group(function () {
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

        Route::middleware('auth:storefront')->group(function () {
            Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
            Route::get('/favorites/list', [FavoriteController::class, 'list'])->name('favorites.list');
            Route::get('/favorites/ids', [FavoriteController::class, 'ids'])->name('favorites.ids');
        });
    });
