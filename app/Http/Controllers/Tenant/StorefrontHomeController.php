<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\HomeVariant;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\BladeThemeService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the storefront home page for a tenant with an active, admin-approved
 * Blade theme. Only reached via ServeBladeThemeHome, which prepends the
 * tenant's uploaded theme directory to the view finder before this runs.
 */
class StorefrontHomeController extends Controller
{
    public function __invoke(StorefrontRepository $repo): View
    {
        $currentCurrency = request()->attributes->get('storefrontCurrentCurrency')
            ?: $repo->currentCurrency();

        $storeName = $repo->storeName();
        $logoPath = $repo->logoPath();
        $footerText = $repo->footerText();
        $footerCopyright = $repo->footerCopyright();
        $socialLinks = $repo->socialLinks();
        $rootCategories = $repo->rootCategoriesWithChildren();
        $categories = $repo->activeCategories();

        $banners             = $repo->activeBanners();
        $flash_sales         = $repo->activeFlashSales();
        $new_arrivals        = $repo->newInProducts(10)->getCollection();
        $recommended_products = $repo->recommendedProducts(10);
        $best_sellers        = $repo->bestSellingProducts(10)->getCollection();
        $trending_products   = $repo->trendingNowProducts(10);
        $featured_products   = $repo->featuredProducts(10);
        $top_rated_products  = $repo->paginatedTopRatedProducts([], 10)->getCollection();

        $homeSections = $this->resolveHomeSections();

        return view('pages.home.index', compact(
            'storeName',
            'logoPath',
            'footerText',
            'footerCopyright',
            'socialLinks',
            'rootCategories',
            'categories',
            'currentCurrency',
            'banners',
            'flash_sales',
            'new_arrivals',
            'recommended_products',
            'best_sellers',
            'trending_products',
            'featured_products',
            'top_rated_products',
            'homeSections',
        ));
    }

    /**
     * Resolves ordered, visible section keys for the active custom blade theme.
     *
     * Priority:
     *   1. Tenant's TenantPageSection rows (visibility + custom order from Page Builder)
     *   2. Fall back to sections discovered from the live-views path
     *
     * @return string[]
     */
    private function resolveHomeSections(): array
    {
        $tenantId = tenant()->getTenantKey();

        // Get the tenant-local 'custom' Theme row for its ID.
        $theme = Theme::query()->where('slug', 'custom')->where('is_active', true)->first();

        if (!$theme) {
            return app(BladeThemeService::class)->discoveredHomeSectionKeys($tenantId);
        }

        // Resolve the active HomeVariant for the custom slug (stored in the central DB).
        $variantId = tenancy()->central(fn () => HomeVariant::query()
            ->where('theme_slug', 'custom')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('id')
        );

        $rows = TenantPageSection::query()
            ->where('theme_id', $theme->id)
            ->when(
                $variantId,
                fn ($q) => $q->where('home_variant_id', $variantId),
                fn ($q) => $q->whereNull('home_variant_id')
            )
            ->where('page', 'home')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->pluck('section_key')
            ->all();

        if (!empty($rows)) {
            return $rows;
        }

        return app(BladeThemeService::class)->discoveredHomeSectionKeys($tenantId);
    }
}
