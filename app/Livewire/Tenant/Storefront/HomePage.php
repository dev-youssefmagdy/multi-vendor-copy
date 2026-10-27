<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Product;
use App\Models\Tenant\Theme;
use App\Models\Tenant\TenantPageSection;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
use App\Services\Tenant\CustomerCountryResolver;
use App\Services\Tenant\HomeVariantResolver;
use App\Services\Tenant\PageBuilder\SectionRegistry;
use Livewire\Component;

class HomePage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    public int $newInPage = 1;
    public int $bestSellingPage = 1;
    public int $flashPage = 1;
    public int $topRatedPage = 1;
    public int $recommendedPage = 1;
    public bool $hasMoreNewIn = false;
    public bool $hasMoreBestSelling = false;
    public bool $hasMoreFlash = false;
    public bool $hasMoreTopRated = false;
    public bool $hasMoreRecommended = false;

    // Active tab for the product tabs on the home page.
    public string $activeProductTab = 'cat-flash';

    protected int $perPage = 10;

    public function loadMoreNewIn(): void
    {
        $this->newInPage++;
    }

    public function loadMoreBestSelling(): void
    {
        $this->bestSellingPage++;
    }

    public function loadMoreFlash(): void
    {
        $this->flashPage++;
    }

    public function loadMoreTopRated(): void
    {
        $this->topRatedPage++;
    }

    public function loadMoreRecommended(): void
    {
        if ($this->hasMoreRecommended) {
            $this->recommendedPage++;
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
        if (!$product) {
            return;
        }

        $repo = app(StorefrontRepository::class);
        $cart = $repo->cart();
        $key = 'p_' . $productId;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);
        $activeVariant = $product->variants->where('active', true)->first();

        $stockError = $this->checkProductStock($product, $activeVariant, 1, $existingQty);
        if ($stockError !== null) {
            $this->dispatch('storefront-toast', message: $stockError, type: 'error');
            return;
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty']++;
        } else {
            $cart[$key] = ['product_id' => $productId, 'qty' => 1];
        }

        session(['storefront_cart' => $cart]);
        app(\App\Services\UserInteractionTracker::class)->trackCart($productId);
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added');
    }

    /**
     * Page data each built-in home view consumes, by section key; '*' lists data the
     * view reads outside its section loop (so it is needed whatever sections show).
     * Built from the views under resources/views/themes/{theme}/pages/home*:
     * a dataset is only computed when a visible section of the rendered view (or
     * the view itself) reads it. Views not listed here get every dataset.
     *
     * Datasets: banners, flash (flashSales/flashProducts/flashBanner/hasMoreFlash),
     * flashCoupon, newIn, bestSelling, topRated, trending, featured, recommended,
     * products (paginatedProducts), buyTogether; the *IfEmpty entries are fallbacks
     * a view only renders when the list it prefers is empty.
     */
    protected const HOME_VIEW_DATA = [
        'themes.ecommet.pages.home' => [
            'hero_banner' => ['banners'],
            'flash_deals' => ['flash'],
            'flash_sale_strip' => ['flash'],
            'best_selling' => ['bestSelling'],
            'new_year_sale' => ['newIn'],
        ],
        'themes.elora.pages.home' => [
            // Inline config script: $hasMoreFlash, $paginatedProducts->hasMorePages().
            '*' => ['flash', 'products'],
            'hero_banner' => ['banners'],
            'flash_sale_strip' => ['flash', 'flashCoupon'],
            'tabbed_products' => ['flash'],
            'trending_now' => ['trending'],
            'best_seller' => ['bestSelling'],
            'new_arrivals' => ['newIn'],
            'recommended_for_you' => ['recommended', 'bestSellingIfNoRecommended', 'products'],
            'shop_by_category' => ['buyTogether'],
        ],
        'themes.elora.pages.home-v2' => self::VARIANT_SECTIONS_DATA,
        'themes.elora.pages.home-v3' => self::VARIANT_SECTIONS_DATA,
        'themes.elora.pages.home-v4' => self::VARIANT_SECTIONS_DATA,
        'themes.elora.pages.home-v5' => self::VARIANT_SECTIONS_DATA,
        'themes.elora.pages.home-v6' => self::VARIANT_SECTIONS_DATA,
        'themes.souqify.pages.home' => [
            // The top @php block aliases these lists for the sections (and the
            // flash countdown script reads $flashSales): treated as always needed.
            '*' => ['banners', 'flash', 'trending', 'bestSelling', 'newIn', 'featured', 'topRated', 'recommended'],
            'special_offers' => ['productsIfNoTopRated'],
            'top_rated_products' => ['productsIfNoTopRated'],
        ],
        'themes.souqify.pages.home-v2' => self::SOUQIFY_VARIANT_SECTIONS_DATA,
        'themes.souqify.pages.home-v3' => self::SOUQIFY_VARIANT_SECTIONS_DATA,
        'themes.souqify.pages.home-v4' => self::SOUQIFY_VARIANT_SECTIONS_DATA,
        'themes.souqify.pages.home-v5' => self::SOUQIFY_VARIANT_SECTIONS_DATA,
        'themes.souqify.pages.home-v6' => self::SOUQIFY_VARIANT_SECTIONS_DATA,
    ];

    /** elora home-v2 … home-v6 (identical section → data shape). */
    protected const VARIANT_SECTIONS_DATA = [
        'hero_banner' => ['banners'],
        'flash_sale_strip' => ['flash'],
        'trending_now' => ['trending'],
        'best_seller' => ['bestSelling'],
        'new_arrivals' => ['newIn'],
        'recommended_for_you' => ['recommended'],
    ];

    /** souqify home-v2 … home-v6 (identical section → data shape). */
    protected const SOUQIFY_VARIANT_SECTIONS_DATA = [
        'hero' => ['banners'],
        'flash_sale' => ['flash'],
        'trending_this_week' => ['trending'],
        'top_products' => ['bestSelling'],
        'new_arrivals' => ['newIn'],
        'recommended_products' => ['recommended'],
    ];

    /** @var array{0: ?Theme, 1: ?\App\Models\HomeVariant, 2: string[], 3: string}|null resolved once per request */
    protected ?array $homeResolution = null;

    /** Resolve the tenant's Theme row + the home variant that applies for the visitor's country. */
    protected function activeThemeAndVariant(): array
    {
        $slug = $this->resolveStrategy()->slug();
        // Same row as Theme::where('slug', $slug)->first(), cached + request-memoized.
        $theme = app(StorefrontRepository::class)->themeBySlug($slug);
        $variant = $theme
            ? app(HomeVariantResolver::class)->resolveFor(
                $theme,
                app(CountryDetectorService::class)->detect(request())
            )
            : null;

        return [$theme, $variant];
    }

    /**
     * [theme, variant, visible section keys, home view] — resolved first so render()
     * only computes the datasets the rendered view actually consumes.
     *
     * @return array{0: ?Theme, 1: ?\App\Models\HomeVariant, 2: string[], 3: string}
     */
    protected function resolvedHome(): array
    {
        if ($this->homeResolution !== null) {
            return $this->homeResolution;
        }

        [$theme, $variant] = $this->activeThemeAndVariant();
        $sections = $this->resolvedHomeSections($theme, $variant);
        $view = app(HomeVariantResolver::class)->viewFor($variant, $this->resolveStrategy());

        return $this->homeResolution = [$theme, $variant, $sections, $view];
    }

    /**
     * Datasets the view needs for these sections, or null when the view is not
     * mapped (uploaded / unknown view): then everything is computed as before.
     *
     * @param  string[]  $sections
     * @return array<string, true>|null
     */
    protected function homeDataNeeds(string $view, array $sections): ?array
    {
        $map = self::HOME_VIEW_DATA[$view] ?? null;

        if ($map === null) {
            return null;
        }

        $needs = $map['*'] ?? [];
        foreach ($sections as $section) {
            array_push($needs, ...($map[$section] ?? []));
        }

        return array_fill_keys($needs, true);
    }

    /** @return string[] visible section keys for the active theme's home page, in order. */
    protected function resolvedHomeSections(?Theme $theme, ?\App\Models\HomeVariant $variant): array
    {
        $slug = $this->resolveStrategy()->slug();

        if (!$theme) {
            return app(HomeVariantResolver::class)->sectionsFor($variant, $slug, null);
        }

        $allDefaultKeys = SectionRegistry::defaultsFor($slug, 'home');

        $rows = TenantPageSection::query()
            ->where('theme_id', $theme->id)
            ->when($variant, fn($q) => $q->where('home_variant_id', $variant->id), fn($q) => $q->whereNull('home_variant_id'))
            ->where('page', 'home')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');

        if ($rows->isEmpty()) {
            return app(HomeVariantResolver::class)->sectionsFor($variant, $slug, null);
        }

        $savedKeys = $rows->sortBy('sort_order')->keys()->all();

        $unsavedKeys = array_values(array_diff($allDefaultKeys, $savedKeys));

        $tenantOrder = collect($savedKeys)
            ->merge($unsavedKeys)
            ->filter(function (string $key) use ($rows) {
                return !$rows->has($key) || (bool) $rows->get($key)->is_visible;
            })
            ->values()
            ->all();

        return app(HomeVariantResolver::class)->sectionsFor($variant, $slug, $tenantOrder);
    }

    public function showProductTab(string $tab): void
    {
        $allowed = ['cat-flash', 'cat-bestselling', 'cat-newin', 'cat-toprated'];
        if (!in_array($tab, $allowed, true)) {
            return;
        }

        $this->activeProductTab = $tab;
        $this->dispatch('refresh-swiper');
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);

        [$activeTheme, $activeVariant, $homeSections, $homeView] = $this->resolvedHome();
        $needs = $this->homeDataNeeds($homeView, $homeSections);
        $wants = fn(string $dataset): bool => $needs === null || isset($needs[$dataset]);

        $newInLimit = $this->perPage * $this->newInPage;
        $bestSellingLimit = $this->perPage * $this->bestSellingPage;
        $topRatedLimit = $this->perPage * $this->topRatedPage;
        $recommendedLimit = $this->perPage * $this->recommendedPage;
        $flashLimit = $this->perPage * $this->flashPage;

        // Lists a visible section doesn't read are passed under the same names, empty.
        $newInProducts = collect();
        $bestSelling = collect();
        $topRatedProducts = collect();
        $trendingNowProducts = new \Illuminate\Database\Eloquent\Collection();
        $featuredProducts = new \Illuminate\Database\Eloquent\Collection();
        $recommendedProducts = new \Illuminate\Database\Eloquent\Collection();
        $buyTogetherProducts = collect();
        $paginatedProducts = null;
        $flashSales = new \Illuminate\Database\Eloquent\Collection();

        // Every list fetched in here is hydrated with ONE merged eager load at the end.
        $repo->batchProductHydration(function () use (
            $repo, $wants, $newInLimit, $bestSellingLimit, $topRatedLimit, $recommendedLimit,
            &$newInProducts, &$bestSelling, &$topRatedProducts, &$trendingNowProducts,
            &$featuredProducts, &$recommendedProducts, &$buyTogetherProducts, &$paginatedProducts,
            &$flashSales, $homeSections
        ): void {
            if ($wants('flash')) {
                $flashSales = $repo->activeFlashSales();
            }

            if ($wants('newIn')) {
                $newInPaginator = $repo->newInProducts($newInLimit, 1);
                $this->hasMoreNewIn = $newInPaginator->total() > $newInLimit;
                $newInProducts = collect($newInPaginator->items());
            } else {
                $this->hasMoreNewIn = false;
            }

            if ($wants('topRated') || $wants('productsIfNoTopRated')) {
                $topRatedPaginator = $repo->homeTopRatedProducts($topRatedLimit, 1);
                $this->hasMoreTopRated = $topRatedPaginator->total() > $topRatedLimit;
                $topRatedProducts = collect($topRatedPaginator->items());
            } else {
                $this->hasMoreTopRated = false;
            }

            if ($wants('trending')) {
                $trendingNowProducts = $repo->trendingNowProducts(10);
            }

            if ($wants('featured')) {
                $featuredProducts = $repo->featuredProducts(10);
            }

            if ($wants('recommended')) {
                $recommendedProducts = $repo->recommendedProducts($recommendedLimit);
                $this->hasMoreRecommended = $recommendedProducts->count() >= $recommendedLimit;
            } else {
                $this->hasMoreRecommended = false;
            }

            if ($wants('bestSelling') || ($wants('bestSellingIfNoRecommended') && $recommendedProducts->isEmpty())) {
                $bestSellingPaginator = $repo->bestSellingProducts($bestSellingLimit, 1);
                $this->hasMoreBestSelling = $bestSellingPaginator->total() > $bestSellingLimit;
                $bestSelling = collect($bestSellingPaginator->items());
            } else {
                $this->hasMoreBestSelling = false;
            }

            if ($wants('products') || ($wants('productsIfNoTopRated') && $topRatedProducts->isEmpty())) {
                $paginatedProducts = $repo->homeProducts($this->perPage);
            }

            // Only the "Shop by Category" section renders the buy-together pair; skip the
            // random pick (and its eager loads) when that section is not on the page.
            if ($wants('buyTogether') && in_array('shop_by_category', $homeSections, true)) {
                $buyTogetherProducts = $repo->randomActiveProducts(2);
            }
        });

        $paginatedProducts ??= new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage, 1, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        $allFlashProducts = $flashSales->flatMap(fn($fs) => $fs->products)->unique('id')->values();
        $this->hasMoreFlash = $allFlashProducts->count() > $flashLimit;
        $flashProducts = $allFlashProducts->take($flashLimit);
        $flashBanner = optional($flashSales->first())->banner_url ?? null;

        // Code advertised on the Flash Sale strip's CTA: the newest currently-valid coupon
        // usable in the customer's country (same validity window CartPage::applyCoupon checks).
        $flashCouponCode = null;
        if ($wants('flashCoupon') && $flashSales->isNotEmpty() && in_array('flash_sale_strip', $homeSections, true)) {
            $countryId = app(CustomerCountryResolver::class)->resolveId();
            $flashCouponCode = Coupon::query()
                ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->forCountry($countryId)
                ->latest()
                ->value('code');
        }

        $data = array_merge($this->sharedData(), [
            'banners' => $wants('banners') ? $repo->activeBanners() : new \Illuminate\Database\Eloquent\Collection(),
            'flashSales' => $flashSales,
            'flashProducts' => $flashProducts,
            'flashBanner' => $flashBanner,
            'flashCouponCode' => $flashCouponCode,
            'newInProducts' => $newInProducts,
            'bestSelling' => $bestSelling,
            'topRatedProducts' => $topRatedProducts,
            'hasMoreNewIn' => $this->hasMoreNewIn,
            'hasMoreBestSelling' => $this->hasMoreBestSelling,
            'hasMoreFlash' => $this->hasMoreFlash,
            'hasMoreTopRated' => $this->hasMoreTopRated,
            'activeProductTab' => $this->activeProductTab,
            'buyTogetherProducts' => $buyTogetherProducts,
            'trendingNowProducts' => $trendingNowProducts,
            'featuredProducts' => $featuredProducts,
            'recommendedProducts' => $recommendedProducts,
            'hasMoreRecommended' => $this->hasMoreRecommended,
            'paginatedProducts' => $paginatedProducts,
            'homeSections' => $homeSections,
        ]);

        $storeName = $repo->storeName();
        $storeDesc = mb_substr(strip_tags($repo->footerText()), 0, 160);

        return view($homeView, $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName,
                'metaDescription' => $storeDesc,
                'ogImage' => $repo->logoPath(),
                'canonicalUrl' => route('tenant.home'),
            ]);
    }
}
