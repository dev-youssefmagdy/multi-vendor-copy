<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasCatalogRefs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRate extends Model
{
    use HasCatalogRefs;

    protected $fillable = ['product_id', 'customer_id', 'stars', 'comment'];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
