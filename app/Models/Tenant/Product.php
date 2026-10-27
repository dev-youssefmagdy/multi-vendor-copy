<?php

namespace App\Models\Tenant;

use App\Services\Tenant\CustomerCountryResolver;
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

    protected array $translated = ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'];

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
            : $this->flashSales()->activeWindow($moment)->forCountry(app(CustomerCountryResolver::class)->resolveId())->get();

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
        $breakdown = static::priceBreakdown($basePrice, $profitType, $profitValue, $shipping);

        return round($breakdown['price'] + $breakdown['fixed_shipping'], 2);
    }

    /**
     * price = cost + profit_amount (profit is a percentage of cost or a fixed amount);
     * the fixed shipping cost is reported separately (docs/ORDER_FORMULAS.md).
     *
     * @return array{cost: float, profit_amount: float, price: float, fixed_shipping: float}
     */
    public static function priceBreakdown(float $cost, string $profitType, float $profitValue, float $shipping): array
    {
        return \App\Support\OrderPricing::unitPrice($cost, $profitType, $profitValue, $shipping);
    }

    /**
     * Compute the final customer-facing price for a given country by applying
     * the configured profit (percentage or fixed) and shipping cost on top of
     * the central sale price.
     */
    public function finalPriceForCountry(?int $countryId): float
    {
        $breakdown = $this->priceBreakdownForCountry($countryId);

        return round($breakdown['price'] + $breakdown['fixed_shipping'], 2);
    }

    /**
     * @return array{cost: float, profit_amount: float, price: float, fixed_shipping: float}
     */
    public function priceBreakdownForCountry(?int $countryId): array
    {
        $basePrice = (float) ($this->centralProduct?->sale_price ?? $this->centralProduct?->base_price ?? 0);

        $profitRows = $this->profit ?? [];
        $profitRow = $profitRows[(string) $countryId] ?? $profitRows['default'] ?? [];
        $profitType = $profitRow['profit_type'] ?? 'percentage';
        $profitValue = (float) ($profitRow['profit_value'] ?? 0);

        $shipping = $this->fixedShippingCostForCountry((int) $countryId);
        if ($shipping === null) {
            $weightGrams = (int) ($this->weight_grams ?? $this->centralProduct?->weight_grams ?? 0);
            $shippingCost = static::activeFixedShippingCostFor($countryId);
            $shipping = $shippingCost ? $shippingCost->costForWeight($weightGrams) : 0.0;
        }

        return static::priceBreakdown($basePrice, $profitType, $profitValue, $shipping);
    }

    /** Request attribute holding the activeFixedShippingCostFor() memo. */
    public const FIXED_SHIPPING_MEMO_ATTRIBUTE = '_fixed_shipping_cost_memo';

    /**
     * Same lookup as `FixedShippingCost::active()->where('country_id', $countryId)->first()`
     * (a null country keeps Eloquent's `where country_id is null` meaning), memoized
     * per request: it runs for every variant-less price calculation, i.e. per card.
     */
    public static function activeFixedShippingCostFor(?int $countryId): ?\App\Models\FixedShippingCost
    {
        $memoKey = $countryId === null ? 'null' : (string) $countryId;
        $attributes = request()->attributes;
        $memo = $attributes->get(self::FIXED_SHIPPING_MEMO_ATTRIBUTE, []);

        if (!array_key_exists($memoKey, $memo)) {
            $memo[$memoKey] = \App\Models\FixedShippingCost::active()
                ->where('country_id', $countryId)
                ->first();
            $attributes->set(self::FIXED_SHIPPING_MEMO_ATTRIBUTE, $memo);
        }

        return $memo[$memoKey];
    }

    /**
     * Gallery + primary image URLs of the central product's files followed by the
     * tenant's own, exactly what the storefront cards computed with
     * `merge()->whereIn('key', [...])->where('file_type', 'image')->map(full_path)
     * ->filter()->unique()->values()->all()` — in one pass over the files.
     *
     * @return array<int, string>
     */
    public function storefrontSliderImageUrls(): array
    {
        $keys = ['gallery', 'primary_medium', 'primary_original'];
        $urls = [];

        foreach ([$this->centralProduct?->files, $this->files] as $files) {
            foreach ($files ?? [] as $file) {
                // whereIn('key', $keys): loose in_array on data_get().
                if (!in_array($file->getAttribute('key'), $keys)) {
                    continue;
                }

                // where('file_type', 'image'): enum value, loose ==, non-stringable object never equal.
                $type = \Illuminate\Support\enum_value($file->getAttribute('file_type'));
                if ((is_object($type) && !$type instanceof \Stringable) || $type != 'image') {
                    continue;
                }

                $urls[] = $file->full_path;
            }
        }

        return array_values(array_unique(array_filter($urls), SORT_REGULAR));
    }

    /**
     * Per-instance memo of storefrontPricing() results (a card reads it several
     * times). Kept off the model (WeakMap) so serialization is unchanged.
     */
    private static ?\WeakMap $storefrontPricingMemo = null;

    public function storefrontPricing(?ProductVariant $variant = null, ?int $countryId = null): array
    {
        $variant ??= $this->variants->firstWhere('active', true) ?? $this->variants->first();

        $resolvedCountryId = $countryId ?? ((int) session('storefront_country_id') ?: null);

        self::$storefrontPricingMemo ??= new \WeakMap();
        $entries = self::$storefrontPricingMemo[$this] ?? [];
        $memoKey = ($variant ? spl_object_id($variant) : 'none') . '|' . ($resolvedCountryId ?? 'null');
        $entry = $entries[$memoKey] ?? null;

        // Hit only while neither model's attributes changed since the entry was
        // stored (identical arrays compare in O(1); cheaper than isDirty()).
        if ($entry !== null && $entry['variant'] === $variant
            && $entry['attributes'] === $this->attributes
            && $entry['variant_attributes'] === $variant?->attributes) {
            return $entry['pricing'];
        }

        // Unsaved attribute changes (e.g. price previews) always recompute.
        if ($this->isDirty() || $variant?->isDirty()) {
            return $this->computeStorefrontPricing($variant, $resolvedCountryId);
        }

        $pricing = $this->computeStorefrontPricing($variant, $resolvedCountryId);
        $entries[$memoKey] = [
            'variant' => $variant,
            'attributes' => $this->attributes,
            'variant_attributes' => $variant?->attributes,
            'pricing' => $pricing,
        ];
        self::$storefrontPricingMemo[$this] = $entries;

        return $pricing;
    }

    private function computeStorefrontPricing(?ProductVariant $variant, ?int $resolvedCountryId): array
    {
        $breakdown = ($variant ?? $this)->priceBreakdownForCountry($resolvedCountryId);
        $_basePrice = round($breakdown['price'] + $breakdown['fixed_shipping'], 2);

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
            'cost' => $breakdown['cost'],
            'profit_amount' => $breakdown['profit_amount'],
            'fixed_shipping' => $breakdown['fixed_shipping'],
        ];
    }

    /**
     * JSON payload stored in a storefront heart button's `data-fav` attribute
     * (HTML-safe, consumed by the favorites toggle scripts).
     */
    public function storefrontFavData($currentCurrency = null): string
    {
        $rate = (float) data_get($currentCurrency, 'conversion_rate', 1.0);
        $pricing = $this->storefrontPricing();
        $hasDiscount = (bool) $pricing['has_discount'];

        return json_encode([
            'slug' => $this->slug,
            'name' => $this->translationValue('name') ?? $this->slug,
            'price' => round((float) $pricing['current_price'] * $rate, 2),
            'old_price' => $hasDiscount && $pricing['original_price'] !== null ? round((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'rating' => (float) ($this->average_rating ?? 0),
            'image' => $this->centralProduct?->primary_image_url ?? $this->primary_image_url ?? null,
            'url' => route('tenant.storefront.product', $this->slug),
            'added' => time(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Maps this product into the plain array shape expected by the elora-v4
     * ("Bold Edition") home page product card partials.
     */
    public function toEloraV4Card($currentCurrency = null): array
    {
        $symbol = data_get($currentCurrency, 'symbol', '$');
        $rate = (float) data_get($currentCurrency, 'conversion_rate', 1.0);

        $pricing = $this->storefrontPricing();
        $sellPrice = (float) $pricing['current_price'];
        $hasDiscount = (bool) $pricing['has_discount'];
        $discountPct = $hasDiscount ? (int) round((float) $pricing['discount_percentage']) : 0;
        $realPrice = $pricing['original_price'];

        $soldCount = (int) ($this->centralProduct?->sold_count ?? 0);
        $stockLeft = (int) ($this->stock ?? 0);
        $soldTotal = $soldCount + $stockLeft;
        $soldPct = $soldTotal > 0 ? (int) round($soldCount / $soldTotal * 100) : 0;

        $weightGrams = $this->centralProduct?->weight_grams ?? $this->weight_grams ?? null;
        $weightLabel = $weightGrams
            ? ($weightGrams >= 1000 ? number_format($weightGrams / 1000, 1) . __('kg') : $weightGrams . __('g'))
            : null;

        $rating = (float) ($this->average_rating ?? 0);
        $ratingCount = $this->relationLoaded('rates') ? $this->rates->count() : $this->rates()->count();

        $favData = json_encode([
            'slug' => $this->slug,
            'name' => $this->translationValue('name') ?? $this->slug,
            'price' => round($sellPrice * $rate, 2),
            'old_price' => $hasDiscount && $realPrice !== null ? round($realPrice * $rate, 2) : null,
            'discount' => $hasDiscount ? $discountPct . '% ' . __('Off') : null,
            'rating' => $rating,
            'image' => $this->centralProduct?->primary_image_url ?? $this->primary_image_url ?? null,
            'url' => route('tenant.storefront.product', $this->slug),
            'added' => time(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => route('tenant.storefront.product', $this->slug),
            'favData' => $favData,
            'image' => $this->centralProduct?->primary_image_url ?? $this->primary_image_url ?? asset('elora-4/assets/images/product-placeholder.svg'),
            'name' => $this->translationValue('name') ?? $this->slug,
            'weight' => $weightLabel,
            'price' => $symbol . number_format($sellPrice * $rate, 2),
            'oldPrice' => $hasDiscount && $realPrice !== null ? $symbol . number_format($realPrice * $rate, 2) : null,
            'discount' => $hasDiscount ? $discountPct . '% ' . __('Off') : null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? ' (+' . $ratingCount . ')' : ''),
            'sold' => $soldPct > 0 ? $soldPct . '% ' . __('Sold') : null,
            'isOutOfStock' => $this->stockStatus() === 'out_of_stock',
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
