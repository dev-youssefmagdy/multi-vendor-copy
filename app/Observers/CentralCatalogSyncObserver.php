<?php

namespace App\Observers;

use App\Jobs\SyncCentralProductPriceToTenantsJob;
use App\Jobs\SyncCentralProductWeightToTenantsJob;
use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
    }

    public function deleting(Category|Product|Variation|ProductVariant $model): void
    {
        $model->tenant_sync_catalog_ids = $this->catalogIdsFor($model);
    }

    public function deleted(Category|Product|Variation|ProductVariant $model): void
    {
        $catalogIds = $model->tenant_sync_catalog_ids ?? $this->catalogIdsFor($model);

        if ($model instanceof Category) {
            app(CentralCatalogTenantSyncService::class)->syncCatalogs((array) $catalogIds, ['categories', 'products']);

            return;
        }

        app(CentralCatalogTenantSyncService::class)->syncCatalogs((array) $catalogIds, ['products']);
    }

    public function restored(Product $model): void
    {
        $catalogIds = app(CentralCatalogTenantSyncService::class)->catalogIdsForProduct($model);

        app(CentralCatalogTenantSyncService::class)->syncCatalogs((array) $catalogIds, ['products']);
    }

    protected function catalogIdsFor(Category|Product|Variation|ProductVariant $model): array
    {
        $syncService = app(CentralCatalogTenantSyncService::class);

        return match (true) {
            $model instanceof Category => $model->catalogs()->pluck('catalogs.id')->all(),
            $model instanceof Product => $syncService->catalogIdsForProduct($model),
            $model instanceof Variation => $syncService->catalogIdsForVariation($model),
            $model instanceof ProductVariant => $syncService->catalogIdsForProductVariant($model),
        };
    }
}
