<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductBadge extends Model
{
    protected $fillable = ['text', 'active'];

    public function getRouteKeyName(): string
    {
        return 'text';
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_badge_product')->withTimestamps();
    }
}
