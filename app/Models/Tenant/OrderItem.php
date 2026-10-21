<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
