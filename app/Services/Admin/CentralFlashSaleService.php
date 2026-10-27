<?php

namespace App\Services\Admin;

use App\Models\CentralFlashSale;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Support\Facades\DB;

class CentralFlashSaleService
{
    public function save(array $attributes, ?CentralFlashSale $flashSale = null): CentralFlashSale
    {
        $saved = DB::transaction(function () use ($attributes, $flashSale) {
            $flashSale ??= new CentralFlashSale();

            $productIds = collect($attributes['product_ids'] ?? [])
                ->filter(fn($id) => filled($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $flashSale->fill([
                'discount_percentage' => $attributes['discount_percentage'],
                'start_date' => $attributes['start_date'] ?? null,
                'end_date' => $attributes['end_date'] ?? null,
                'active' => (bool) ($attributes['active'] ?? true),
            ]);

            // Quiet save: the tenant sync must run after products and countries are written.
            $flashSale->saveQuietly();

            $flashSale->products()->sync($productIds);

            if (array_key_exists('country_ids', $attributes)) {
                $flashSale->syncCountries($attributes['country_ids']);
            }

            return $flashSale->fresh('products.translations.language');
        });

        app(CentralCatalogTenantSyncService::class)->syncFlashSales();

        return $saved;
    }

    public function delete(CentralFlashSale $flashSale): void
    {
        $flashSale->delete();
    }
}
