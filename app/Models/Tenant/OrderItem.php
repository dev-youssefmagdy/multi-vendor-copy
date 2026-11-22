<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasCatalogRefs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasCatalogRefs;
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'qty',
        'cost',
        'profit_amount',
        'price',
        'sub_total',
        'fixed_shipping',
        'discount',
        'discount_source',
        'tax',
        'shipping_fee',
        'weight',
        'grand_total',
        // Dedup plan phase 1 typed refs (ORDER_FORMULAS.md §4); set by OrderComposer, else derived on save.
        'product_type',
        'catalog_product_id',
        'variant_type',
        'catalog_variant_id',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'cost' => 'decimal:2',
            'profit_amount' => 'decimal:2',
            'price' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'fixed_shipping' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'weight' => 'decimal:3',
            'grand_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Merged key of the line's product / variant from the typed pair (dedup plan phase 5); NULL for legacy rows. */
    public function getProductKeyAttribute(): ?int
    {
        return \App\Support\Catalog\ProductKey::fromPair($this->attributes['product_type'] ?? null, $this->attributes['catalog_product_id'] ?? null);
    }

    public function getVariantKeyAttribute(): ?int
    {
        return \App\Support\Catalog\ProductKey::fromPair($this->attributes['variant_type'] ?? null, $this->attributes['catalog_variant_id'] ?? null);
    }

    public function variant(): BelongsTo
    {
        if (\App\Repositories\Tenant\Catalog\ProductCatalog::enabled()) {
            return $this->belongsTo(\App\Models\Tenant\Catalog\MergedProductVariant::class, 'variant_key');
        }

        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product(): BelongsTo
    {
        if (\App\Repositories\Tenant\Catalog\ProductCatalog::enabled()) {
            return $this->belongsTo(\App\Models\Tenant\Catalog\MergedProduct::class, 'product_key');
        }

        return $this->belongsTo(Product::class);
    }
}
