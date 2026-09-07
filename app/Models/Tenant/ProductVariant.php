<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'central_product_variant_id',
        'title',
        'sku',
        'weight_grams',
        'option_ids',
        'real_price',
        'sell_price',
        'default_sell_price',
        'profit',
        'stock',
        'margin_percentage',
        'thumbnail_path',
        'active',
    ];

    protected $appends = ['thumbnail_url', 'display_label'];

    protected function casts(): array
    {
        return [
            'weight_grams' => 'integer',
            'real_price' => 'decimal:2',
            'sell_price' => 'array',
            'default_sell_price' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
            'stock' => 'integer',
            'option_ids' => 'array',
            'active' => 'boolean',
            'profit' => 'array',
        ];
    }

    /**
     * Return the per-country sell price stored in the JSON `sell_price` column.
     * Falls back to the "default" key, then to `default_sell_price`.
     */
    public function sellPriceForCountry(?int $countryId): float
    {
        $prices = $this->sell_price ?? [];

        if ($countryId && isset($prices[(string) $countryId])) {
            return (float) $prices[(string) $countryId];
        }

        if (isset($prices['default'])) {
            return (float) $prices['default'];
        }

        return (float) ($this->default_sell_price ?? 0);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function centralVariant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductVariant::class, 'central_product_variant_id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            if (Str::startsWith($this->thumbnail_path, ['http://', 'https://'])) {
                return $this->thumbnail_path;
            }

            // Strip legacy 'storage/' prefix stored before tenant_asset was used.
            $path = Str::startsWith($this->thumbnail_path, 'storage/')
                ? Str::after($this->thumbnail_path, 'storage/')
                : $this->thumbnail_path;

            return tenant_asset($path);
        }

        // Fallback chain: central variant thumb → tenant/central product
        // primary image (the tenant Product accessor already cascades to the
        // central product when no tenant file exists).
        $centralVariant = $this->centralVariant;
        if ($centralVariant) {
            $centralFile = $centralVariant->relationLoaded('files')
                ? $centralVariant->files->firstWhere('key', 'thumb')
                : $centralVariant->files()->where('key', 'thumb')->first();

            if ($centralFile?->full_path) {
                return $centralFile->full_path;
            }
        }

        return $this->product?->primary_image_url;
    }

    /**
     * Absolute filesystem path to the variant image, for local calls (e.g. the
     * Python AI price-finder service). Mirrors getThumbnailUrlAttribute's
     * fallback chain but resolves to a local path instead of a URL.
     */
    public function getThumbnailLocalPathAttribute(): ?string
    {
        if ($this->thumbnail_path && !Str::startsWith($this->thumbnail_path, ['http://', 'https://'])) {
            $path = Str::startsWith($this->thumbnail_path, 'storage/')
                ? Str::after($this->thumbnail_path, 'storage/')
                : $this->thumbnail_path;

            $local = Storage::disk('public')->path($path);
            if (is_file($local)) {
                return $local;
            }
        }

        $centralVariant = $this->centralVariant;
        if ($centralVariant) {
            $centralFile = $centralVariant->relationLoaded('files')
                ? $centralVariant->files->firstWhere('key', 'thumb')
                : $centralVariant->files()->where('key', 'thumb')->first();

            if ($centralFile?->local_path) {
                return $centralFile->local_path;
            }
        }

        return $this->product?->primary_image_local_path;
    }

    /**
     * Human-friendly variant label, e.g. "Small / Red".
     * Resolves from central variant title (for synced products) or from the
     * tenant-stored option_ids JSON (for own products).
     */
    public function getDisplayLabelAttribute(): ?string
    {
        $centralTitle = $this->centralVariant?->title;
        if (filled($centralTitle)) {
            return $centralTitle;
        }

        $optionIds = (array) ($this->option_ids ?? []);
        if ($optionIds === []) {
            return null;
        }

        $cacheKey = 'variant_option_labels:' . md5(implode(',', array_map('intval', $optionIds)));
        $labels = \Illuminate\Support\Facades\Cache::driver('file')->remember($cacheKey, 600, function () use ($optionIds) {
            /** @var \Illuminate\Support\Collection<int, \App\Models\VariationOption> $options */
            $options = \App\Models\VariationOption::query()
                ->with('translations.language')
                ->whereIn('id', $optionIds)
                ->get();

            return $options
                ->map(fn(\App\Models\VariationOption $opt) => $opt->translationValue('name'))
                ->filter()
                ->all();
        });

        return $labels === [] ? null : implode(' / ', $labels);
    }

    function fixedShippingCostForCountry(?int $countryId): ?float
    {
        $raw = $this->centralVariant?->fixed_shipping_costs;
        $fixedArr = is_array($raw) ? $raw : (array) json_decode($raw ?: '[]', true);
        $fixedShippingCost = $fixedArr[(string) $countryId] ?? 0;
        return $fixedShippingCost;
    }

    /**
     * Compute the final customer-facing price for a given country by applying
     * the configured profit (percentage or fixed) and shipping cost on top of
     * the variant's real price. Mirrors the formula used by the price-list
     * modal (see ProductsList::computeVariantPrices).
     */
    public function finalPriceForCountry(?int $countryId): float
    {
        $basePrice = (float) ($this->real_price ?? 0);

        $profitRows = $this->profit ?? [];
        $profitRow = $profitRows[(string) $countryId] ?? $profitRows['default'] ?? [];
        $profitType = $profitRow['profit_type'] ?? 'percentage';
        $profitValue = (float) ($profitRow['profit_value'] ?? 0);
        // dd($basePrice,$profitRow);
        $shipping = $this->fixedShippingCostForCountry((int) $countryId);

        if ($shipping === null) {
            $weightGrams = (int) ($this->weight_grams ?? $this->product?->weight_grams ?? $this->product?->centralProduct?->weight_grams ?? 0);
            $shippingCost = \App\Models\FixedShippingCost::active()
                ->where('country_id', $countryId)
                ->first();
            $shipping = $shippingCost ? $shippingCost->costForWeight($weightGrams) : 0.0;
        }

        return Product::computeFinalPrice($basePrice, $profitType, $profitValue, $shipping);
    }
}
