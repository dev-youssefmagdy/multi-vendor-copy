<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasCatalogRefs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRate extends Model
{
    use HasCatalogRefs;

    protected $fillable = ['product_id', 'customer_id', 'stars', 'comment', 'product_type', 'catalog_product_id'];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
        ];
    }

    /** Merged key of the product from the typed pair (dedup plan phase 5); NULL for legacy rows without a pair. */
    public function getProductKeyAttribute(): ?int
    {
        return \App\Support\Catalog\ProductKey::fromPair($this->attributes['product_type'] ?? null, $this->attributes['catalog_product_id'] ?? null);
    }

    public function product(): BelongsTo
    {
        if (\App\Repositories\Tenant\Catalog\ProductCatalog::enabled()) {
            return $this->belongsTo(\App\Models\Tenant\Catalog\MergedProduct::class, 'product_key');
        }

        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
