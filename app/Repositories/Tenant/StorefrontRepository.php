<?php

namespace App\Repositories\Tenant;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Language;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\SocialLink;
use App\Models\Tenant\Theme;
use App\Models\DeliveryPopupDay;
use App\Services\CountryDetectorService;
use App\Services\Tenant\CustomerCountryResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Read-only data access for the customer-facing storefront.
 * All methods operate inside the tenant DB context (tenancy already initialized by middleware).
 */
class StorefrontRepository
{
    /**
     * Selectable text-logo fonts per language: setting value => label + CSS stack.
     * Shared by the storefront resolver and the vendor-panel logo builder.
     */
    public const LOGO_FONTS = [
        'ar' => [
            'cairo' => ['label' => 'Cairo', 'family' => "'Cairo', sans-serif"],
            'tajawal' => ['label' => 'Tajawal', 'family' => "'Tajawal', sans-serif"],
            'almarai' => ['label' => 'Almarai', 'family' => "'Almarai', sans-serif"],
            'amiri' => ['label' => 'Amiri', 'family' => "'Amiri', serif"],
        ],
        'en' => [
            'poppins' => ['label' => 'Poppins', 'family' => "'Poppins', sans-serif"],
            'playfair' => ['label' => 'Playfair Display', 'family' => "'Playfair Display', serif"],
            'montserrat' => ['label' => 'Montserrat', 'family' => "'Montserrat', sans-serif"],
            'oswald' => ['label' => 'Oswald', 'family' => "'Oswald', sans-serif"],
        ],
    ];

    /** In-request memoization: populated once, reused for every subsequent call. */
    protected array $memo = [];

    private function detectedCountry(): ?\App\Models\Country
    {
        return $this->memo['detected_country'] ??= app(CountryDetectorService::class)->detect(request());
    }

    protected function customerCountryId(): ?int
    {
        if (array_key_exists('customer_country_id', $this->memo)) {
            return $this->memo['customer_country_id'];
        }

        return $this->memo['customer_country_id'] = app(CustomerCountryResolver::class)->resolveId();
    }

    protected function basePriceExpression(): string
    {
        // Mirrors ProductVariant::sellPriceForCountry() / Product::priceForCountry():
        // prefer the per-country override stored in the JSON price column, then the
        // "default" key, then fall back to the scalar companion column. This keeps
        // filtering/sorting consistent with the price actually shown to the visitor.
        $countryId = $this->customerCountryId();

        if ($countryId) {
            return "COALESCE("
                . "(SELECT MIN(COALESCE("
                . "CAST(JSON_UNQUOTE(JSON_EXTRACT(pv.sell_price, '$.\"" . (int) $countryId . "\"')) AS DECIMAL(12,4)), "
                . "CAST(JSON_UNQUOTE(JSON_EXTRACT(pv.sell_price, '$.default')) AS DECIMAL(12,4)), "
                . "pv.default_sell_price"
                . ")) FROM product_variants pv WHERE pv.product_id = products.id AND pv.active = 1), "
                . "COALESCE("
                . "CAST(JSON_UNQUOTE(JSON_EXTRACT(products.price, '$.\"" . (int) $countryId . "\"')) AS DECIMAL(12,4)), "
                . "CAST(JSON_UNQUOTE(JSON_EXTRACT(products.price, '$.default')) AS DECIMAL(12,4)), "
                . "products.default_price"
                . ")"
                . ")";
        }

        // No resolved country: use the scalar companion columns (default_sell_price /
        // default_price) which are kept in sync with the JSON price columns and are
        // safe for SQL ORDER BY and WHERE comparisons.
        return "COALESCE((SELECT MIN(pv.default_sell_price) FROM product_variants pv WHERE pv.product_id = products.id AND pv.active = 1), products.default_price)";
    }

    protected function activeFlashDiscountExpression(): string
    {
        return "COALESCE((SELECT MAX(flash_sales.discount_percentage) FROM flash_sale_product INNER JOIN flash_sales ON flash_sales.id = flash_sale_product.flash_sale_id WHERE flash_sale_product.product_id = products.id AND flash_sales.active = 1 AND (flash_sales.start_date IS NULL OR flash_sales.start_date <= CURRENT_TIMESTAMP) AND (flash_sales.end_date IS NULL OR flash_sales.end_date >= CURRENT_TIMESTAMP)), 0)";
    }

    protected function effectivePriceExpression(): string
    {
        return '(' . $this->basePriceExpression() . ' * (1 - (' . $this->activeFlashDiscountExpression() . ' / 100)))';
    }

    protected function averageRatingExpression(): string
    {
        return "COALESCE((SELECT AVG(product_rates.stars) FROM product_rates WHERE product_rates.product_id = products.id), 0)";
    }

    protected function productRelations(bool $withCategories = false): array
    {
        $relations = [
            'variants',
            'variants.centralVariant',
            'flashSales' => fn($query) => $query->activeWindow()->orderByDesc('discount_percentage'),
            'translations.language',
            'files',
            'badges',
            'rates',
            'centralProduct' => fn($query) => $query->with([
                'files',
                'shippingZones.rates',
            ]),
        ];

        if ($withCategories) {
            $relations[] = 'categories.translations.language';
            $relations[] = 'categories.files';
        }

        return $relations;
    }

    protected function productBaseQuery(bool $withCategories = false, bool $excludeOutOfStock = false): Builder
    {
        $query = Product::query()
            ->where('active', true)
            // ->whereRaw($this->basePriceExpression() . ' > 0')
            ->with($this->productRelations($withCategories));

        return $excludeOutOfStock ? $this->excludeOutOfStock($query) : $query;
    }

    protected function excludeOutOfStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where(function (Builder $q): void {
                // No-variant products: stock management enabled and stock depleted
                $q->whereDoesntHave('variants')
                    ->where('manage_stock', true)
                    ->where('stock', '<=', 0);
            })->orWhere(function (Builder $q): void {
                // Variant products: total variant stock is zero or negative
                $q->whereHas('variants')
                    ->whereRaw('(SELECT COALESCE(SUM(pv.stock), 0) FROM product_variants pv WHERE pv.product_id = products.id) <= 0');
            });
        }, boolean: 'and not');
    }

    protected function applyProductFilters($query, array $filters): Builder
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $availability = (string) ($filters['availability'] ?? '');
        $productFlag = (string) ($filters['product_flag'] ?? '');
        $sale = (string) ($filters['on_sale'] ?? '');
        $minRating = $filters['ratings'] ?? null;

        // Section presets: apply filter-side logic for section-based navigation
        $section = (string) ($filters['section'] ?? '');
        if ($section === 'flash_sale' && $sale === '') {
            $sale = 'flash_sale';
        } elseif ($section === 'featured' && $productFlag === '') {
            $productFlag = 'featured';
        } elseif ($section === 'hot_deals' && $sale === '') {
            $sale = 'on_sale';
        }
        $minPrice = $filters['min'] ?? null;
        $maxPrice = $filters['max'] ?? null;

        if ($keyword !== '') {
            // 1. Fetch matching Central Product IDs (runs on Central DB)
            $matchingCentralProductIds = \App\Models\Product::where('slug', 'like', '%' . $keyword . '%')
                ->orWhere('sku', 'like', '%' . $keyword . '%')
                ->orWhereHas('translations', function (Builder $translationQuery) use ($keyword): void {
                    $translationQuery->where('field', 'name')
                        ->where('value', 'like', '%' . $keyword . '%');
                })
                ->pluck('id');

            // 2. Fetch matching Central Variant IDs (runs on Central DB)
            $matchingCentralVariantIds = \App\Models\ProductVariant::where('sku', 'like', '%' . $keyword . '%')
                ->orWhere('title', 'like', '%' . $keyword . '%')
                ->pluck('id');

            // 3. Your main Tenant Query (runs on Tenant DB)
            $query->where(function (Builder $searchQuery) use ($keyword, $matchingCentralProductIds, $matchingCentralVariantIds): void {
                $searchQuery
                    // Match Tenant Slug
                    ->where('slug', 'like', '%' . $keyword . '%')

                    // Match Tenant Translations
                    ->orWhereHas('translations', function (Builder $translationQuery) use ($keyword): void {
                        $translationQuery->where('field', 'name')
                            ->where('value', 'like', '%' . $keyword . '%');
                    })

                    // Match Central Product by ID
                    ->orWhereIn('central_product_id', $matchingCentralProductIds)

                    // Match Variants tied to matching Central Variants
                    ->orWhereHas('variants', function (Builder $variantQuery) use ($matchingCentralVariantIds): void {
                        $variantQuery->whereIn('central_product_variant_id', $matchingCentralVariantIds);
                    });
            });
        }

        if ($availability === 'in_stock') {
            $query->where(function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->whereDoesntHave('variants')
                        ->where(function (Builder $q): void {
                            $q->where('manage_stock', false)
                                ->orWhere('stock', '>', 0);
                        });
                })->orWhere(function (Builder $q): void {
                    $q->whereHas('variants')
                        ->whereRaw('(SELECT COALESCE(SUM(pv.stock), 0) FROM product_variants pv WHERE pv.product_id = products.id) > 0');
                });
            });
        } elseif ($availability === 'out_of_stock') {
            $query->where(function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->whereDoesntHave('variants')
                        ->where('manage_stock', true)
                        ->where('stock', '<=', 0);
                })->orWhere(function (Builder $q): void {
                    $q->whereHas('variants')
                        ->whereRaw('(SELECT COALESCE(SUM(pv.stock), 0) FROM product_variants pv WHERE pv.product_id = products.id) <= 0');
                });
            });
        }

        if ($productFlag === 'featured') {
            $query->where('featured', true);
        }

        if ($sale === 'on_sale') {
            $countryId = $this->customerCountryId();
            $effectiveSellPriceSql = $countryId
                ? "COALESCE("
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(product_variants.sell_price, '$.\"" . (int) $countryId . "\"')) AS DECIMAL(12,4)), "
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(product_variants.sell_price, '$.default')) AS DECIMAL(12,4)), "
                    . "product_variants.default_sell_price"
                    . ")"
                : "COALESCE("
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(product_variants.sell_price, '$.default')) AS DECIMAL(12,4)), "
                    . "product_variants.default_sell_price"
                    . ")";

            $query->whereExists(function ($subQuery) use ($effectiveSellPriceSql): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('product_variants.active', true)
                    ->whereRaw('product_variants.real_price > ' . $effectiveSellPriceSql);
            });
        } elseif ($sale === 'flash_sale') {
            $query->whereHas('flashSales', function (Builder $flashSaleQuery): void {
                $flashSaleQuery->activeWindow();
            });
        }

        if (is_numeric($minRating) && (int) $minRating >= 1) {
            $query->whereRaw($this->averageRatingExpression() . ' >= ?', [(int) $minRating]);
        }

        $currencyRate = (float) ($this->currentCurrency()?->conversion_rate ?? 1.0);
        if ($currencyRate <= 0) {
            $currencyRate = 1.0;
        }

        if (is_numeric($minPrice)) {
            $query->whereRaw($this->effectivePriceExpression() . ' >= ?', [(float) $minPrice / $currencyRate]);
        }

        if (is_numeric($maxPrice)) {
            $query->whereRaw($this->effectivePriceExpression() . ' <= ?', [(float) $maxPrice / $currencyRate]);
        }

        return $query;
    }

    protected function applyProductSort($query, string $sort): Builder
    {
        return match ($sort) {
            'old' => $query->orderBy('created_at'),
            'ascending' => $query->orderByRaw($this->effectivePriceExpression() . ' asc')->orderByDesc('created_at'),
            'descending' => $query->orderByRaw($this->effectivePriceExpression() . ' desc')->orderByDesc('created_at'),
            'rating' => $query->orderByRaw($this->averageRatingExpression() . ' desc')->orderByDesc('created_at'),
            'best_selling' => $query->orderByRaw('(SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi LEFT JOIN product_variants pv ON pv.id = oi.product_variant_id WHERE COALESCE(oi.product_id, pv.product_id) = products.id) DESC')->orderByDesc('created_at'),
            default => $query->orderByRaw($this->effectivePriceExpression() . ' asc')->orderByDesc('created_at'),
        };
    }

    protected function applyCategoryFilter($query, int|string|null $categoryId): Builder
    {
        if (!is_numeric($categoryId) || (int) $categoryId <= 0) {
            return $query;
        }

        return $query->whereHas('categories', fn(Builder $categoryQuery) => $categoryQuery->where('categories.id', (int) $categoryId));
    }

    protected function productsByBadgeQuery(string $badgeText, bool $excludeOutOfStock = false): Builder
    {
        return $this->productBaseQuery(excludeOutOfStock: $excludeOutOfStock)
            ->whereHas('badges', fn(Builder $badgeQuery) => $badgeQuery->where('text', $badgeText)->where('active', true));
    }

    // ─── Appearance ─────────────────────────────────────────────────────────

    public function settings(): array
    {
        return Setting::query()
            ->where('group', 'appearance')
            ->pluck('value', 'name')
            ->all();
    }

    /**
     * Fetches store_name / logo settings / footer_text / footer_copyright in one
     * query, keyed by name. Result is memoized for the lifetime of this instance.
     *
     * @return array<string, \App\Models\Tenant\Setting>
     */
    protected function appearanceSettings(): array
    {
        return $this->memo['appearance_settings'] ??= Setting::query()
            ->whereIn('name', [
                'store_name',
                'logo_path',
                'footer_text',
                'footer_copyright',
                'logo_mode',
                'logo_text_ar',
                'logo_text_en',
                'logo_color',
                'logo_bg_color',
                'logo_shape',
                'logo_font_ar',
                'logo_font_en',
                'logo_path_ar',
                'logo_path_en',
            ])
            ->with('translations.language')
            ->get()
            ->keyBy('name')
            ->all();
    }

    public function storeName(): string
    {
        $setting = $this->appearanceSettings()['store_name'] ?? null;

        return (string) ($setting?->translationValue('value') ?? $setting?->value ?: config('app.name', 'Store'));
    }

    public function logoPath(): ?string
    {
        $logo = $this->resolvedLogo();

        return $logo['mode'] === 'image' ? $logo['image_url'] : null;
    }

    /**
     * The logo to render for the current locale: either the uploaded image for
     * that language, or a styled text wordmark. Falls back to a text logo built
     * from the store name when image mode is on but no image was uploaded.
     *
     * @return array{mode:string,image_url:?string,text:string,font_family:string,color:string,bg_color:string,shape:string}
     */
    public function resolvedLogo(): array
    {
        $settings = $this->appearanceSettings();
        $value = fn(string $name) => (string) (($settings[$name] ?? null)?->value ?? '');

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $fonts = self::LOGO_FONTS[$locale];

        $fontKey = $value('logo_font_' . $locale);
        $fontFamily = $fonts[$fontKey]['family'] ?? reset($fonts)['family'];

        $text = $value('logo_text_' . $locale) ?: $this->storeName();
        $color = $value('logo_color') ?: '#111827';
        $bgColor = $value('logo_bg_color') ?: '#ffffff';
        $shape = $value('logo_shape') === 'rounded' ? 'rounded' : 'rectangle';

        $imageUrl = $value('logo_path_' . $locale) ?: $value('logo_path') ?: null;
        $mode = $value('logo_mode') === 'text' ? 'text' : 'image';

        if ($mode === 'image' && !$imageUrl) {
            $mode = 'text';
        }

        return [
            'mode' => $mode,
            'image_url' => $mode === 'image' ? $imageUrl : null,
            'text' => $text,
            'font_family' => $fontFamily,
            'color' => $color,
            'bg_color' => $bgColor,
            'shape' => $shape,
        ];
    }

    public function footerText(): string
    {
        $setting = $this->appearanceSettings()['footer_text'] ?? null;

        return (string) ($setting?->translationValue('value') ?? $setting?->value ?: '');
    }

    public function footerCopyright(): string
    {
        $setting = $this->appearanceSettings()['footer_copyright'] ?? null;

        return (string) ($setting?->translationValue('value') ?? $setting?->value)
            ?: __(':year All rights reserved.', ['year' => date('Y')]);
    }

    public function socialLinks(): Collection
    {
        return $this->memo['social_links'] ??= SocialLink::query()->orderBy('serial_number')->get();
    }

    // ─── Languages & Currencies ──────────────────────────────────────────────

    public function activeLanguages(): Collection
    {
        return $this->memo['active_languages'] ??= Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderByDesc('is_default')

            ->with('imageFile')
            ->get();
    }

    public function activeCurrencies(): Collection
    {
        return $this->memo['active_currencies'] ??= Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
    }

    public function currentLanguage(): ?Language
    {
        if (array_key_exists('current_language', $this->memo)) {
            return $this->memo['current_language'];
        }

        $code = Session::get('storefront_language');

        if ($code) {
            $lang = Language::query()->where('code', $code)->where('is_active', true)->with('imageFile')->first();
            if ($lang) {
                return $this->memo['current_language'] = $lang;
            }
        }

        // Auto-detect from visitor's country when no session preference is set.
        $country = $this->detectedCountry();
        if ($country?->language_code) {
            $detected = Language::query()
                ->where('code', $country->language_code)
                ->where('is_active', true)
                ->with('imageFile')
                ->first();
            if ($detected) {
                return $this->memo['current_language'] = $detected;
            }
        }

        return $this->memo['current_language'] = Language::query()->where('is_default', true)->with('imageFile')->first()
            ?? Language::query()->where('is_active', true)->with('imageFile')->first();
    }

    public function currentCurrency(): ?Currency
    {
        if (array_key_exists('current_currency', $this->memo)) {
            return $this->memo['current_currency'];
        }

        $code = Session::get('storefront_currency');

        if ($code) {
            $cur = Currency::query()->where('code', $code)->where('is_active', true)->first();
            if ($cur) {
                return $this->memo['current_currency'] = $cur;
            }
        }

        // Auto-detect from visitor's country when no session preference is set.
        $country = $this->detectedCountry();
        if ($country?->currency_code) {
            $detected = Currency::query()
                ->where('code', $country->currency_code)
                ->where('is_active', true)
                ->first();
            if ($detected) {
                return $this->memo['current_currency'] = $detected;
            }
        }

        return $this->memo['current_currency'] = Currency::query()->where('is_default', true)->first()
            ?? Currency::query()->where('is_active', true)->first();
    }


    // ─── Theme resolution ────────────────────────────────────────────────────

    /**
     * Resolve the theme that should render for the current visitor.
     *
     * Priority (highest → lowest):
     *   1. A country-specific ACTIVE theme whose `theme_country` pivot contains
     *      the visitor's country with `is_enabled = true`. If several match,
     *      the MOST SPECIFIC one (fewest allowed countries) wins — because it
     *      was authored for a narrower audience and is more likely tailored.
     *   2. A universal ACTIVE theme (no country restrictions).
     *   3. Any universal theme (active flag ignored) as a last resort so the
     *      storefront never ends up with no theme at all.
     */
    public function currentTheme(): ?Theme
    {
        if (\array_key_exists('current_theme', $this->memo)) {
            return $this->memo['current_theme'];
        }

        $country = $this->detectedCountry();

        if ($country) {
            // Find country-specific active themes allowing this country, ordered
            // by specificity (ascending total allowed country count). We join the
            // pivot twice — once to filter, once to count — which is fine for the
            // small number of themes a tenant has.
            $specific = Theme::query()
                ->where('is_universal', false)
                ->where('is_active', true)
                ->whereExists(function ($q) use ($country) {
                    $q->select(DB::raw(1))
                        ->from('theme_country')
                        ->whereColumn('theme_country.theme_id', 'themes.id')
                        ->where('theme_country.country_id', $country->id)
                        ->where('theme_country.is_enabled', true);
                })
                ->withCount([
                    'countries as allowed_countries_count' => function ($q) {
                        $q->where('is_enabled', true);
                    }
                ])
                ->orderBy('allowed_countries_count')
                ->orderBy('id')
                ->first();

            if ($specific) {
                return $this->memo['current_theme'] = $specific;
            }
        }

        return $this->memo['current_theme'] = Theme::query()
            ->where('is_universal', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->first()
            ?? Theme::query()->where('is_universal', true)->orderBy('id')->first();
    }


    // ─── Banners ─────────────────────────────────────────────────────────────

    public function activeBanners(): Collection
    {
        if (\array_key_exists('active_banners', $this->memo)) {
            return $this->memo['active_banners'];
        }

        $countryId = $this->detectedCountry()?->id;

        if ($countryId) {
            $countryBanners = Banner::query()
                ->with('translations.language')
                ->where('country_id', $countryId)
                ->orderBy('serial_number')
                ->get();

            if ($countryBanners->isNotEmpty()) {
                return $this->memo['active_banners'] = $countryBanners;
            }
        }

        return $this->memo['active_banners'] = Banner::query()
            ->with('translations.language')
            ->whereNull('country_id')
            ->orderBy('serial_number')
            ->get();
    }

    // ─── Categories ──────────────────────────────────────────────────────────

    private function categoryDescendantIds(int $categoryId): array
    {
        $all = $this->activeCategories();
        $ids = [];
        $queue = [$categoryId];
        while ($queue) {
            $parentId = array_shift($queue);
            foreach ($all->where('parent_id', $parentId) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }
        return $ids;
    }

    public function activeCategories(): Collection
    {
        return $this->memo['active_categories'] ??= Category::query()
            ->where('active', true)
            ->withCount(['products', 'children']) // For fallback thumb in header menu
            ->with(['translations.language', 'files', 'centralCategory.files'])
            ->orderBy('order_number')
            ->get()
            ->filter(fn(Category $c) => $c->children_count > 0 || $c->products_count > 0)
            ->values();
    }

    public function rootCategoriesWithChildren(): Collection
    {
        return $this->memo['root_categories'] ??= $this->pruneEmptyLeaves(
            Category::query()
                ->where('active', true)
                ->whereNull('parent_id')
                ->with([
                    'translations.language',
                    'centralCategory.files',
                    'files',
                    'children' => fn($q) => $q->where('active', true)->orderBy('order_number'),
                    'children.translations.language',
                    'children.files',
                    'children.centralCategory.files',
                    'children.children' => fn($q) => $q->where('active', true)->orderBy('order_number'),
                    'children.children.translations.language',
                    'children.children.files',
                    'children.children.centralCategory.files',
                ])
                ->withCount('products')
                ->orderBy('order_number')
                ->get()
        );
    }

    /**
     * Recursively remove leaf categories (no children) that have no products,
     * then bubble up — a parent becomes a leaf if all its children are pruned.
     */
    private function pruneEmptyLeaves(Collection $categories): Collection
    {
        // Ensure products_count is loaded for any category that doesn't have it yet
        $missing = $categories->filter(fn(Category $c) => !isset($c->products_count));
        if ($missing->isNotEmpty()) {
            $missing->loadCount('products');
        }

        return $categories
            ->map(function (Category $category) {
                if ($category->children->isNotEmpty()) {
                    $category->setRelation('children', $this->pruneEmptyLeaves($category->children));
                }
                return $category;
            })
            ->filter(fn(Category $c) => $c->children->isNotEmpty() || ($c->products_count ?? 0) > 0)
            ->values();
    }

    public function categoryBySlug(string|null $slug): ?Category
    {
        if (!$slug) {
            return null;
        }

        $locale = app()->getLocale();

        return Category::query()
            ->where('active', true)
            ->whereHas(
                'translations',
                fn($t) => $t
                    ->where('field', 'slug')
                    ->where('value', $slug)
                    ->whereHas('language', fn($l) => $l->where('code', $locale))
            )
            ->with(['translations.language', 'files'])
            ->first()
            ?? Category::query()
                ->where('active', true)
                ->whereHas(
                    'translations',
                    fn($t) => $t
                        ->where('field', 'slug')
                        ->where('value', $slug)
                )
                ->with(['translations.language', 'files'])
                ->first();
    }

    // ─── Products ────────────────────────────────────────────────────────────

    public function featuredProducts(int $limit = 10): Collection
    {
        return app(\App\Services\HomeProductService::class)->getFeatured($limit, $this->customerCountryId());
    }

    public function latestProducts(int $limit = 20): Collection
    {
        return $this->productBaseQuery()
            ->orderBy('order_number')
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function productsByCategory(Category $category, int $limit = 20): Collection
    {
        return $this->productBaseQuery()
            ->join('category_product', function ($join) use ($category) {
                $join->on('category_product.product_id', '=', 'products.id')
                    ->where('category_product.category_id', $category->id);
            })
            ->orderBy('category_product.sort_order', 'asc')
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->orderByDesc('products.created_at')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    public function paginatedProductsByCategory(?Category $category = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $userSort = (string) ($filters['sort'] ?? 'latest');
        $sort = $userSort;

        // Section presets: override sort when the user hasn't explicitly chosen one
        if ($sort === 'latest') {
            $section = (string) ($filters['section'] ?? '');
            $sort = match ($section) {
                'trending', 'top_products', 'recommended' => 'best_selling',
                'new_arrivals' => 'latest',
                'special_offers', 'top_rated' => 'rating',
                default => $sort,
            };
        }

        $categoryIdWithChildrenIds = $category ? array_merge([$category->id], $this->categoryDescendantIds($category->id)) : null;
        // dd($categoryIdWithChildrenIds);
        $query = $this->productBaseQuery(excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock');

        if ($category && $userSort === 'latest') {
            $query->join('category_product', function ($join) use ($categoryIdWithChildrenIds) {
                $join->on('category_product.product_id', '=', 'products.id')
                    ->whereIn('category_product.category_id', $categoryIdWithChildrenIds);
            })
                ->orderBy('category_product.sort_order', 'asc')
                ->orderByDesc('products.created_at')
                ->select('products.*');

            $this->applyProductFilters($query, $filters);
        } else {
            $query->when($categoryIdWithChildrenIds, fn($categoryQuery) => $categoryQuery->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIdWithChildrenIds)));

            $this->applyProductFilters($query, $filters);
            $this->applyProductSort($query, $sort);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchProducts(string $query, int $limit = 24): Collection
    {
        return $this->excludeOutOfStock(
            Product::query()
                ->where('active', true)
                // ->whereRaw($this->basePriceExpression() . ' > 0')
                ->whereHas('translations', fn($q) => $q
                    ->where('field', 'name')
                    ->where('value', 'like', '%' . $query . '%'))
                ->with($this->productRelations())
        )
            ->limit($limit)
            ->get();
    }

    public function autocompleteProducts(string $keyword, int $limit = 8): Collection
    {
        // Fetch matching Central Product IDs (runs on Central DB)
        $matchingCentralProductIds = \App\Models\Product::where('slug', 'like', '%' . $keyword . '%')
            ->orWhereHas('translations', fn(Builder $t) => $t->where('field', 'name')->where('value', 'like', '%' . $keyword . '%'))
            ->pluck('id');

        return $this->excludeOutOfStock(
            Product::query()
                ->where('active', true)
                // ->whereRaw($this->basePriceExpression() . ' > 0')
                ->with($this->productRelations())
        )
            ->where(function (Builder $q) use ($keyword, $matchingCentralProductIds): void {
                $q->where('slug', 'like', '%' . $keyword . '%')
                    ->orWhereHas('translations', fn(Builder $t) => $t->where('field', 'name')->where('value', 'like', '%' . $keyword . '%'))
                    ->orWhereIn('central_product_id', $matchingCentralProductIds);
            })
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
     * Load tenant products for a ranked list of central catalog product IDs
     * (as returned by ImageSearchService::search), preserving similarity order.
     *
     * @param array<int> $centralProductIds ordered most-similar first
     */
    public function imageSearchProducts(array $centralProductIds, int $perPage = 20): LengthAwarePaginator
    {
        if (empty($centralProductIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $orderClause = 'FIELD(central_product_id, ' . implode(',', array_map('intval', $centralProductIds)) . ')';

        $query = $this->productBaseQuery()
            ->whereIn('central_product_id', $centralProductIds)
            ->orderByRaw($orderClause);

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginatedSearchProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'latest');

        $query = $this->productBaseQuery(excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock');

        $this->applyProductFilters($query, $filters);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);
        $this->applyProductSort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    public function productBySlug(string $slug): ?Product
    {
        return $this->excludeOutOfStock(
            Product::query()
                ->where('active', true)
                // ->whereRaw($this->basePriceExpression() . ' > 0')
                ->where('slug', $slug)
                ->with($this->productRelations(true))
        )
            ->first();
    }

    public function productsByBadge(string $badgeText, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->productsByBadgeQuery($badgeText)
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginatedProductsByBadge(string $badgeText, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'latest');

        $query = $this->productsByBadgeQuery($badgeText, excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock');

        $this->applyProductFilters($query, $filters);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);
        $this->applyProductSort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginatedTopRatedProducts(array $filters = [], int $perPage = 20, int $minimumRating = 5): LengthAwarePaginator
    {
        $query = $this->productBaseQuery(excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock');

        $this->applyProductFilters($query, $filters);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);

        $query
            ->whereRaw($this->averageRatingExpression() . ' >= ?', [$minimumRating])
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->withQueryString();
    }

    public function trendingNowProducts(int $limit = 10): Collection
    {
        $key = 'trending_now_' . $limit;
        return $this->memo[$key] ??= (function () use ($limit): Collection{
            $salesQuery = OrderItem::query()
                ->selectRaw('COALESCE(order_items.product_id, product_variants.product_id) as product_id, SUM(order_items.qty) as total_qty')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where(function ($query): void{
                    $query->whereNotNull('order_items.product_id')
                        ->orWhereNotNull('product_variants.product_id');
                })
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->groupBy(DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'))
                ->orderByDesc('total_qty')
                ->limit($limit);

            return $this->productBaseQuery()
                ->joinSub($salesQuery, 'sales_totals', fn($join) => $join->on('sales_totals.product_id', '=', 'products.id'))
                ->select('products.*')
                ->orderByRaw($this->effectivePriceExpression() . ' asc')
                ->get();
        })();
    }

    public function paginatedBestSellingProducts(?int $days = 30, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $salesQuery = OrderItem::query()
            ->selectRaw('COALESCE(order_items.product_id, product_variants.product_id) as product_id, SUM(order_items.qty) as total_qty')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where(function ($query): void {
                $query->whereNotNull('order_items.product_id')
                    ->orWhereNotNull('product_variants.product_id');
            })
            ->when($days, fn($query) => $query->where('orders.created_at', '>=', now()->subDays($days)))
            ->groupBy(DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'));

        if (!(clone $salesQuery)->exists()) {
            return $this->paginatedProductsByBadge('best-selling', $filters, $perPage);
        }

        $query = $this->productBaseQuery(excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock')
            ->joinSub($salesQuery, 'sales_totals', fn($join) => $join->on('sales_totals.product_id', '=', 'products.id'))
            ->select('products.*');

        $this->applyProductFilters($query, $filters);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);

        return $query
            ->orderByRaw($this->effectivePriceExpression() . ' asc')
            ->orderByDesc('products.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function bestSellingProducts(int $perPage = 12, int $page = 1): LengthAwarePaginator
    {
        return $this->paginateFromService(
            fn(int $limit) => app(\App\Services\HomeProductService::class)->getBestSelling($limit, $this->customerCountryId()),
            $perPage,
            $page,
        );
    }

    public function newInProducts(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->paginateFromService(
            fn(int $limit) => app(\App\Services\HomeProductService::class)->getNewIn($limit, $this->customerCountryId()),
            $perPage,
            $page,
        );
    }

    protected function paginateFromService(callable $fetcher, int $perPage, int $page): LengthAwarePaginator
    {
        $limit = $perPage * $page;
        $items = $fetcher($limit);
        $total = $items->count() >= $limit ? $items->count() + 1 : $items->count();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
        );
    }

    public function recommendedProducts(int $limit = 10): Collection
    {
        return $this->memo['recommended_' . $limit . '_' . ($this->customerCountryId() ?? 'def')] ??= app(\App\Services\HomeProductService::class)->getRecommended($limit, $this->customerCountryId());
    }

    public function paginatedProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'latest');

        $query = $this->productBaseQuery(excludeOutOfStock: ($filters['availability'] ?? '') !== 'out_of_stock');

        $this->applyProductFilters($query, $filters);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);
        $this->applyProductSort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    public function activeBadges(): Collection
    {
        return ProductBadge::query()->where('active', true)->orderBy('text')->get();
    }

    // ─── Flash Sales ─────────────────────────────────────────────────────────

    public function activeFlashSales(): Collection
    {
        return $this->memo['active_flash_sales'] ??= FlashSale::query()
            ->activeWindow()
            ->with([
                'files',
                'products' => fn($query) => $query->with($this->productRelations()),
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    // ─── Cart (session-based) ────────────────────────────────────────────────

    /**
     * Return the session cart array.
     * Structure: [ 'v_12' => ['product_id' => 1, 'variant_id' => 12, 'qty' => 2], 'p_5' => ['product_id' => 5, 'qty' => 1], ... ]
     */
    public function cart(): array
    {
        return Session::get('storefront_cart', []);
    }

    public function cartItems(): array
    {
        $cart = $this->cart();

        if ($cart === []) {
            return [];
        }

        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->values();
        $variantIds = collect($cart)->pluck('variant_id')->filter()->unique()->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with($this->productRelations())
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->with([
                'centralVariant',
                'product' => fn($query) => $query->with($this->productRelations()),
            ])
            ->get()
            ->keyBy('id');

        // Resolve the visitor's country for per-country pricing: selected/default
        // customer address takes priority over the session-detected country.
        $countryId = app(CustomerCountryResolver::class)->resolveId();

        $items = [];

        foreach ($cart as $key => $entry) {
            $variant = isset($entry['variant_id']) ? $variants->get((int) $entry['variant_id']) : null;
            $product = $variant?->product ?? $products->get((int) ($entry['product_id'] ?? 0));

            if (!$product) {
                continue;
            }

            $variant ??= $product->variants->firstWhere('active', true) ?? $product->variants->first();

            $pricing = $product->storefrontPricing($variant, $countryId);
            $price = (float) $pricing['current_price'];
            $qty = max(1, (int) ($entry['qty'] ?? 1));

            $items[] = [
                'key' => $key,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product' => $product,
                'variant' => $variant,
                'qty' => $qty,
                'price' => $price,
                'original_price' => $pricing['original_price'],
                'base_price' => $pricing['base_price'],
                'has_discount' => $pricing['has_discount'],
                'discount_percentage' => $pricing['discount_percentage'],
                'is_flash_sale' => $pricing['is_flash_sale'],
                'flash_sale_percentage' => $pricing['flash_sale_percentage'],
                'subtotal' => $price * $qty,
                'is_out_of_stock' => $this->isCartItemOutOfStock($product, $variant),
            ];
        }

        return $items;
    }

    private function isCartItemOutOfStock(Product $product, ?ProductVariant $variant): bool
    {
        $central = $product->centralProduct;

        if (!$central) {
            $stock = $variant ? ($variant->stock ?? $product->stock ?? null) : $product->stock;
            return $stock !== null && (int) $stock <= 0;
        }

        if (!($central->manage_stock ?? false)) {
            return false;
        }

        if ($variant) {
            $stock = (int) ($variant->stock ?? $variant->centralVariant?->stock ?? 0);
        } else {
            $stock = (int) ($product->stock ?? 0);
        }

        return $stock <= 0;
    }

    public function cartCount(): int
    {
        return (int) collect($this->cart())->sum('qty');
    }

    public function cartTotal(): float
    {
        return (float) collect($this->cartItems())->sum('subtotal');
    }

    // ─── Orders ──────────────────────────────────────────────────────────────

    public function orderByUuid(string $uuid): ?Order
    {
        return Order::query()
            ->where('uuid', $uuid)
            ->with([
                'items.product.files',
                'items.product.translations.language',
                'items.variant.product.files',
                'items.variant.product.translations.language',
                'items.variant.centralVariant',
                'customer',
                'activities',
                'paymentGateway',
            ])
            ->first();
    }

    public function customerOrders(?Customer $customer): Collection
    {
        if (!$customer) {
            return new Collection();
        }

        return Order::query()
            ->where('customer_id', $customer->id)
            ->with([
                'items.product.files',
                'items.product.translations.language',
                'items.variant.product.files',
                'items.variant.product.translations.language',
                'items.variant.centralVariant',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Delivery popup distribution data from the central DB.
     * Keyed by day_number for quick lookup, ordered by day ascending.
     *
     * @return \Illuminate\Support\Collection<int, DeliveryPopupDay>
     */
    /**
     * @param  int|null  $countryId  Detected country for the current storefront visitor.
     *                               When provided, returns country-specific records first;
     *                               falls back to global-default records (country_id IS NULL)
     *                               when no country-specific entries exist.
     *
     * @return \Illuminate\Support\Collection<int, DeliveryPopupDay>
     */
    public function deliveryPopupDays(?int $countryId = null): \Illuminate\Support\Collection
    {
        $cacheKey = 'delivery_popup_days_' . ($countryId ?? 'default');

        return $this->memo[$cacheKey] ??= tenancy()->central(function () use ($countryId) {
            if ($countryId !== null) {
                $records = DeliveryPopupDay::query()
                    ->where('country_id', $countryId)
                    ->orderBy('day_number')
                    ->get();

                if ($records->isNotEmpty()) {
                    return $records;
                }
            }

            // Fallback: global default entries (country_id IS NULL)
            return DeliveryPopupDay::query()
                ->whereNull('country_id')
                ->orderBy('day_number')
                ->get();
        });
    }
}
