<?php

namespace App\Observers;

use App\Jobs\SyncCentralProductPriceToTenantsJob;
use App\Jobs\SyncCentralProductWeightToTenantsJob;
use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Variation;
use App\Services\Tenant\CentralCatalogTenantSyncService;

class CentralCatalogSyncObserver
{
    public function updated(Product|ProductVariant $model): void
    {
        if ($model->wasChanged('weight_grams')) {
            if ($model instanceof Product) {
                info("Dispatching SyncCentralProductWeightToTenantsJob for central product #{$model->id}");
                SyncProductFixedShippingCosts::dispatch(productId: $model->id);
                SyncCentralProductWeightToTenantsJob::dispatch(centralProductId: $model->id);
            } else {
                info("Dispatching SyncCentralProductWeightToTenantsJob for central variant #{$model->id}");
                SyncProductFixedShippingCosts::dispatch(variantId: $model->id);
                SyncCentralProductWeightToTenantsJob::dispatch(centralVariantId: $model->id);
            }
        }

        $priceChanged = $model instanceof Product
            ? $model->wasChanged(['base_price', 'sale_price'])
            : $model->wasChanged('price');

        if ($priceChanged) {
            if ($model instanceof Product) {
                info("Dispatching SyncCentralProductPriceToTenantsJob for central product #{$model->id}");
                SyncCentralProductPriceToTenantsJob::dispatch(centralProductId: $model->id);
            } else {
                info("Dispatching SyncCentralProductPriceToTenantsJob for central variant #{$model->id}");
                SyncCentralProductPriceToTenantsJob::dispatch(centralVariantId: $model->id);
            }
        }

        if ($model instanceof ProductVariant && $model->wasChanged('stock')) {
            app(CentralCatalogTenantSyncService::class)->syncProductVariant($model);
        }

        // When a central product's publication status changes, immediately update
        // the central_visible flag on every tenant's copy of the product.
        // This handles published → archived (hide from tenants) and
        // archived → published (restore to tenants) without needing a manual sync.
        if ($model instanceof Product && $model->wasChanged('status')) {
            $allTenants = Tenant::query()->orderBy('id')->get();

            foreach ($allTenants as $tenant) {
                tenancy()->initialize($tenant);
                try {
                    // Use withoutGlobalScope so we can find the row even when
                    // central_visible is currently false (archived product).
                    \App\Models\Tenant\Product::withoutGlobalScope('centralVisible')
                        ->where('central_product_id', $model->id)
                        ->update(['central_visible' => $model->isVisibleToTenants()]);
                } finally {
                    tenancy()->end();
                }
            }
        }
    }

    public function deleting(Category|Product|Variation|ProductVariant $model): void
    {
        //
    }

    public function deleted(Category|Product|Variation|ProductVariant $model): void
    {
        if ($model instanceof Category) {
            app(CentralCatalogTenantSyncService::class)->syncAllTenants(['categories', 'products']);

            return;
        }

        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['products']);
    }

    public function restored(Product $model): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['products']);
    }
}
