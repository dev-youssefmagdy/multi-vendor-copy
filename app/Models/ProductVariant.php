<?php

namespace App\Models;

use App\Enums\VariationStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProductVariant extends Model
{
    use HasFactory, HasTranslations, CentralConnection;

    protected array $translated = ['title'];

    protected $fillable = [
        'product_id',
        'sku',
        'title',
        'weight_grams',
        'fixed_shipping_costs',
        'price',
        'stock',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'weight_grams' => 'integer',
            'fixed_shipping_costs' => 'array',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'position' => 'integer',
            'status' => VariationStatus::class,
        ];
    }

    /**
     * Resolve the fixed shipping cost for a given country from this variant's
     * per-variant JSON column. Returns null if no entry exists for that country.
     */
    public function fixedShippingCostForCountry(int $countryId): ?float
    {
        if ($countryId <= 0) {
            return null;
        }
        $costs = $this->fixed_shipping_costs ?? [];
        $key = (string) $countryId;

        return array_key_exists($key, $costs) ? (float) $costs[$key] : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(VariationOption::class, 'product_variant_option')->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
