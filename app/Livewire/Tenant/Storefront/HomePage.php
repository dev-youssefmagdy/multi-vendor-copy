<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Product;
use App\Models\Tenant\Theme;
use App\Models\Tenant\TenantPageSection;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
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
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added');
    }

    /** Resolve the tenant's Theme row + the home variant that applies for the visitor's country. */
    protected function activeThemeAndVariant(): array
    {
        $slug = $this->resolveStrategy()->slug();
        $theme = Theme::query()->where('slug', $slug)->first();
        $variant = $theme
            ? app(HomeVariantResolver::class)->resolveFor(
                $theme,
                app(CountryDetectorService::class)->detect(request())
            )
            : null;

        return [$theme, $variant];
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

        $newInLimit = $this->perPage * $this->newInPage;
        $bestSellingLimit = $this->perPage * $this->bestSellingPage;
        $topRatedLimit = $this->perPage * $this->topRatedPage;

        $newInPaginator = $repo->newInProducts($newInLimit, 1);
        $bestSellingPaginator = $repo->bestSellingProducts($bestSellingLimit, 1);
        $topRatedPaginator = $repo->paginatedTopRatedProducts([], $topRatedLimit, 1);

        $trendingNowProducts = $repo->trendingNowProducts(10);

        $recommendedLimit = $this->perPage * $this->recommendedPage;
        $recommendedProducts = $repo->recommendedProducts($recommendedLimit);
        $this->hasMoreRecommended = $recommendedProducts->count() >= $recommendedLimit;

        $this->hasMoreNewIn = $newInPaginator->total() > $newInLimit;
        $this->hasMoreBestSelling = $bestSellingPaginator->total() > $bestSellingLimit;
        $this->hasMoreTopRated = $topRatedPaginator->total() > $topRatedLimit;

        $flashSales = $repo->activeFlashSales();
        $allFlashProducts = $flashSales->flatMap(fn($fs) => $fs->products)->unique('id')->values();
        $flashLimit = $this->perPage * $this->flashPage;
        $this->hasMoreFlash = $allFlashProducts->count() > $flashLimit;
        $flashProducts = $allFlashProducts->take($flashLimit);
        $flashBanner = optional($flashSales->first())->banner_url ?? null;

        $buyTogetherProducts = Product::with([
            'variants',
            'centralProduct',
            'rates',
            'flashSales' => fn($q) => $q->activeWindow()->orderByDesc('discount_percentage'),
        ])
            ->where('active', true)
            ->inRandomOrder()
            ->limit(2)
            ->get();


        $paginatedProducts = $repo->paginatedProducts([], $this->perPage, 1);

        [$activeTheme, $activeVariant] = $this->activeThemeAndVariant();

        $data = array_merge($this->sharedData(), [
            'banners' => $repo->activeBanners(),
            'flashSales' => $flashSales,
            'flashProducts' => $flashProducts,
            'flashBanner' => $flashBanner,
            'newInProducts' => collect($newInPaginator->items()),
            'bestSelling' => collect($bestSellingPaginator->items()),
            'topRatedProducts' => collect($topRatedPaginator->items()),
            'hasMoreNewIn' => $this->hasMoreNewIn,
            'hasMoreBestSelling' => $this->hasMoreBestSelling,
            'hasMoreFlash' => $this->hasMoreFlash,
            'hasMoreTopRated' => $this->hasMoreTopRated,
            'activeProductTab' => $this->activeProductTab,
            'buyTogetherProducts' => $buyTogetherProducts,
            'trendingNowProducts' => $trendingNowProducts,
            'recommendedProducts' => $recommendedProducts,
            'hasMoreRecommended' => $this->hasMoreRecommended,
            'paginatedProducts' => $paginatedProducts,
            'homeSections' => $this->resolvedHomeSections($activeTheme, $activeVariant),
        ]);

        $storeName = $repo->storeName();
        $storeDesc = mb_substr(strip_tags($repo->footerText()), 0, 160);

        $homeView = app(HomeVariantResolver::class)->viewFor($activeVariant, $this->resolveStrategy());
        return view($homeView, $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName,
                'metaDescription' => $storeDesc,
                'ogImage' => $repo->logoPath(),
                'canonicalUrl' => route('tenant.home'),
            ]);
    }
}
