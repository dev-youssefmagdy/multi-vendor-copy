<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Tenant mirror of App\Models\ProductLabel (read-only on the storefront). */
class ProductLabel extends Model
{
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

    /** Text in the current app locale, falling back to the default `text`. */
    public function localizedText(?string $locale = null): string
    {
        $translated = trim((string) data_get($this->translations, $locale ?? app()->getLocale()));

        return $translated !== '' ? $translated : (string) $this->text;
    }

    /** Replace {stock} / {delivery_date} for one product. */
    public function resolveText(int $stock): string
    {
        return strtr($this->localizedText(), [
            '{stock}' => $stock > 99 ? '+99' : (string) max(0, $stock),
            '{delivery_date}' => Carbon::now()->addDays(3)->translatedFormat('d F'),
        ]);
    }
}
