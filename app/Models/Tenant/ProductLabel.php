<?php

namespace App\Models\Tenant;

use App\Models\Concerns\ProductLabelText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Tenant mirror of App\Models\ProductLabel (read-only on the storefront). */
class ProductLabel extends Model
{
    use ProductLabelText;

    protected $fillable = ['central_product_label_id', 'text', 'translations', 'icon', 'active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_label_product')->withTimestamps();
    }
}
