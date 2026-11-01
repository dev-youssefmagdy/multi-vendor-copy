<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Icon + text chip shown on storefront product cards. Managed centrally and assigned
 * to products from either side (label form or product form). `text` supports the
 * tokens {stock} and {delivery_date}, resolved per product at render time.
 */
class ProductLabel extends Model
{
    use CentralConnection;

    protected $fillable = ['text', 'icon', 'active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_label_product')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Replace {stock} / {delivery_date} for one product. */
    public function resolveText(int $stock): string
    {
        return strtr($this->text, [
            '{stock}' => $stock > 99 ? '+99' : (string) max(0, $stock),
            '{delivery_date}' => Carbon::now()->addDays(3)->translatedFormat('d F'),
        ]);
    }
}
