<?php

namespace App\Models\Tenant;

use App\Support\Catalog\CatalogRefResolver;
use App\Support\Catalog\CatalogRefWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Server-side cart of a mobile customer: only product/variant/qty (prices are always recomputed). */
class MobileCart extends Model
{
    protected $fillable = ['customer_id', 'items', 'coupon_code'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    /**
     * Dedup plan phase 1: every item also carries its typed refs (product_type / catalog_product_id, variant_type /
     * catalog_variant_id). Only items without them are resolved (one batch per save); readers still use
     * product_id / variant_id. JSON keys need no migration; a failure never blocks the cart save.
     */
    protected static function booted(): void
    {
        static::saving(function (self $cart) {
            $items = $cart->items;
            if (! is_array($items) || $items === []) {
                return;
            }

            try {
                $enriched = (new CatalogRefWriter(CatalogRefResolver::on($cart->getConnection())))->enrichCartItems($items);
                if ($enriched !== $items) {
                    $cart->items = $enriched;
                }
            } catch (Throwable $e) {
                Log::warning('Mobile cart typed refs not filled (picked up by catalog:backfill-refs).', ['error' => $e->getMessage()]);
            }
        });
    }
}
