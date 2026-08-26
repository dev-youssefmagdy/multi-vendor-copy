<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Hide products whose linked central product is currently draft/archived
     * or soft-deleted from every tenant-panel and storefront query, without
     * touching the vendor's own `active` toggle. TenantCatalogSyncService
     * keeps this column in sync with the central product's status on every
     * sync run, so it flips back automatically when the admin republishes
     * or restores the product.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('centralVisible', function (Builder $builder) {
            $builder->where('central_visible', true);
        });
    }

    protected array $translated = ['name', 'description', 'meta_keywords'];

    protected $with = ['files'];

    protected $fillable = [
        'central_product_id',
        'has_custom_translations',
        'needs_ai_translation',
        'ai_translated_at',
        'sku',
        'slug',
        'price',
        'default_price',
        'sale_price',
        'cost_price',
        'active',
        'central_visible',
        'allowed_country_ids',
        'featured',
        'is_own_product',
        'is_tenant_owned',
        'requires_shipping',
        'stock',
        'min_stock',
        'manage_stock',
        'is_taxable',
        'weight_grams',
        'social_posts',
        'social_image_b64',
        'ai_price_data',
        'fixed_shipping_costs',
        'profit',
        'order_number',
        'return_policy_override',
        'is_returnable',
        'return_window_days',
        'return_fee',
        'return_video_required',
        'return_conditions',
    ];

    protected $appends = ['primary_image_url', 'average_rating'];

    protected function casts(): array
    {
        return [
            'price' => 'array',
            'default_price' => 'decimal:2',
            'active' => 'boolean',
            'has_custom_translations' => 'boolean',
            'needs_ai_translation' => 'boolean',
            'ai_translated_at' => 'datetime',
            'central_visible' => 'boolean',
            'allowed_country_ids' => 'array',
            'featured' => 'boolean',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_own_product' => 'boolean',
            'is_tenant_owned' => 'boolean',
            'requires_shipping' => 'boolean',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'manage_stock' => 'boolean',
            'is_taxable' => 'boolean',
            'weight_grams' => 'integer',
            'social_posts' => 'array',
            'ai_price_data' => 'array',
            'fixed_shipping_costs' => 'array',
            'profit' => 'array',
            'return_policy_override' => 'boolean',
            'is_returnable' => 'boolean',
            'return_window_days' => 'integer',
            'return_fee' => 'decimal:2',
            'return_video_required' => 'boolean',
        ];
    }

    /**
     * Resolve the flat fixed shipping cost for a given country.
     * Checks the tenant-level JSON column first; falls back to the central
     * product's JSON column if no entry exists at the tenant level.
     * Returns null if there is no configured cost for that country.
     */
    public function fixedShippingCostForCountry(int $countryId): ?float
    {
        if ($countryId <= 0) {
            return null;
        }
        $key = (string) $countryId;

        $tenantCosts = $this->fixed_shipping_costs ?? [];

        if (array_key_exists($key, $tenantCosts)) {
            return (float) $tenantCosts[$key];
        }

        $centralCosts = $this->centralProduct?->fixed_shipping_costs ?? [];
        if (array_key_exists($key, $centralCosts)) {
            return (float) $centralCosts[$key];
        }

        return null;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function flashSales(): BelongsToMany
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_product')->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model')->orderBy('sort_order')->orderBy('id');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(ProductBadge::class, 'product_badge_product')->withTimestamps();
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ProductRate::class);
    }

    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'central_product_id');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'primary_medium') ?? $this->files->firstWhere('key', 'primary_original')
            : $this->files()->whereIn('key', ['primary_medium', 'primary_original', 'gallery'])->orderByDesc('key')->first();

        if ($file?->full_path) {
            return $file->full_path;
        }

        // Fallback: when the tenant did not upload a primary image, use the
        // central product's primary image so storefront themes always render
        // the synced media instead of a placeholder.
        $centralUrl = $this->centralProduct?->primary_image_url;
        if ($centralUrl) {
            return $centralUrl;
        }

        return asset('elora/assets/images/product-default.png');
    }

    /**
     * Absolute filesystem path to the primary image, for local calls (e.g. the
     * Python AI price-finder service) that read the file directly instead of
     * fetching it over HTTP. Null when no locally-stored file is available.
     */
    public function getPrimaryImageLocalPathAttribute(): ?string
    {
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'primary_medium') ?? $this->files->firstWhere('key', 'primary_original')
            : $this->files()->whereIn('key', ['primary_medium', 'primary_original', 'gallery'])->orderByDesc('key')->first();

        if ($file?->local_path) {
            return $file->local_path;
        }

        return $this->centralProduct?->primary_image_local_path;
    }

    public function getAverageRatingAttribute(): float
    {
        if ($this->relationLoaded('rates')) {
            return $this->rates->isEmpty() ? 0.0 : (float) $this->rates->avg('stars');
        }

        return (float) $this->rates()->avg('stars') ?? 0.0;
    }

    public function activeStorefrontFlashSale(): ?FlashSale
    {
        $moment = Carbon::now();

        $flashSales = $this->relationLoaded('flashSales')
            ? $this->flashSales
            : $this->flashSales()->activeWindow($moment)->get();

        return $flashSales
            ->filter(fn(FlashSale $flashSale) => $flashSale->isCurrentlyActive($moment))
            ->sortByDesc(fn(FlashSale $flashSale) => (float) $flashSale->discount_percentage)
            ->first();
    }

    /**
     * Return the per-country sell price stored in the JSON `price` column.
     * Falls back to the "default" key, then to `default_price`.
     */
    public function priceForCountry(?int $countryId): float
    {
        $prices = $this->price ?? [];

        if ($countryId && isset($prices[(string) $countryId])) {
            return (float) $prices[(string) $countryId];
        }

        if (isset($prices['default'])) {
            return (float) $prices['default'];
        }

        return (float) ($this->default_price ?? 0);
    }

    /**
     * Shared "your price" formula: base + profit (percentage or fixed) + shipping.
     * Single source of truth used by finalPriceForCountry() on both Product and
     * ProductVariant, and by the price-list modal (ProductsList Livewire component)
     * so the preview, save, and storefront display can never drift apart.
     */
    public static function computeFinalPrice(float $basePrice, string $profitType, float $profitValue, float $shipping): float
    {
        $profitAmount = $profitType === 'fixed'
            ? $profitValue
            : ($basePrice * $profitValue / 100);

        return round($basePrice + $profitAmount + $shipping, 2);
    }

    /**
     * Compute the final customer-facing price for a given country by applying
     * the configured profit (percentage or fixed) and shipping cost on top of
     * the central sale price.
     */
    public function finalPriceForCountry(?int $countryId): float
    {
        $basePrice = (float) ($this->centralProduct?->sale_price ?? $this->centralProduct?->base_price ?? 0);

        $profitRows = $this->profit ?? [];
        $profitRow = $profitRows[(string) $countryId] ?? $profitRows['default'] ?? [];
        $profitType = $profitRow['profit_type'] ?? 'percentage';
        $profitValue = (float) ($profitRow['profit_value'] ?? 0);

        $shipping = $this->fixedShippingCostForCountry((int) $countryId);
        if ($shipping === null) {
            $weightGrams = (int) ($this->weight_grams ?? $this->centralProduct?->weight_grams ?? 0);
            $shippingCost = \App\Models\FixedShippingCost::active()
                ->where('country_id', $countryId)
                ->first();
            $shipping = $shippingCost ? $shippingCost->costForWeight($weightGrams) : 0.0;
        }

        return static::computeFinalPrice($basePrice, $profitType, $profitValue, $shipping);
    }

    public function storefrontPricing(?ProductVariant $variant = null, ?int $countryId = null): array
    {
        $variant ??= $this->variants->firstWhere('active', true) ?? $this->variants->first();

        $resolvedCountryId = $countryId ?? ((int) session('storefront_country_id') ?: null);

        if ($variant) {
            $_basePrice = $variant->finalPriceForCountry($resolvedCountryId);
        } else {
            $_basePrice = $this->finalPriceForCountry($resolvedCountryId);
        }

        $basePrice = $_basePrice;
        // Only treat real_price as an original (strikethrough) price if the vendor
        // explicitly set it higher than their sell price (vendor-side discount).
        // For synced central products real_price is the platform cost which is always
        // lower than sell_price, so it must NOT appear as a strikethrough.
        $originalPrice = ($variant?->real_price !== null && (float) $variant->real_price > $basePrice)
            ? (float) $variant->real_price
            : null;

        $flashSale = $this->activeStorefrontFlashSale();
        $flashDiscountPercentage = (float) ($flashSale?->discount_percentage ?? 0);

        $currentPrice = $basePrice;

        if ($flashDiscountPercentage > 0) {
            $currentPrice = round($basePrice * (1 - ($flashDiscountPercentage / 100)), 2);
            // Strikethrough is always the vendor's normal sell price, not the central cost.
            $originalPrice = $basePrice;
        }

        $hasDiscount = $originalPrice !== null && $originalPrice > $currentPrice;
        $discountPercentage = $hasDiscount && $originalPrice > 0
            ? round((1 - ($currentPrice / $originalPrice)) * 100, 2)
            : 0.0;

        return [
            'price' => round($_basePrice, 2),
            'base_price' => round($basePrice, 2),
            'current_price' => round($currentPrice, 2),
            'original_price' => $hasDiscount ? round($originalPrice, 2) : null,
            'has_discount' => $hasDiscount,
            'discount_percentage' => $discountPercentage,
            'is_flash_sale' => $flashDiscountPercentage > 0,
            'flash_sale_percentage' => round($flashDiscountPercentage, 2),
            'flash_sale' => $flashSale,
        ];
    }

    /**
     * 'out_of_stock' when every variant (or the product itself, if it has no
     * variants) has zero stock; 'partial' when only some variants are
     * depleted; otherwise 'in_stock'.
     */
    public function stockStatus(): string
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        if ($variants->isEmpty()) {
            return (int) ($this->stock ?? 0) > 0 ? 'in_stock' : 'out_of_stock';
        }

        $outCount = $variants->filter(fn(ProductVariant $variant) => (int) $variant->stock <= 0)->count();

        if ($outCount === 0) {
            return 'in_stock';
        }

        return $outCount === $variants->count() ? 'out_of_stock' : 'partial';
    }
}
