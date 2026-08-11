<?php

namespace App\Services\Admin;

use App\Models\CentralFlashSale;
use Illuminate\Support\Facades\DB;

class CentralFlashSaleService
{
    public function save(array $attributes, ?CentralFlashSale $flashSale = null): CentralFlashSale
    {
        return DB::transaction(function () use ($attributes, $flashSale) {
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

            $flashSale->save();

            $flashSale->products()->sync($productIds);

            return $flashSale->fresh('products.translations.language');
        });
    }

    public function delete(CentralFlashSale $flashSale): void
    {
        $flashSale->delete();
    }
}
