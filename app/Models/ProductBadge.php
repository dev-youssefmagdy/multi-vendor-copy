<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProductBadge extends Model
{
    use CentralConnection;

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
