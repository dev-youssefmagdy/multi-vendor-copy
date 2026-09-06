<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Repositories\Tenant\StorefrontRepository;
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

        $banners = $repo->activeBanners();
        $flash_sales = $repo->activeFlashSales();
        $new_arrivals = $repo->newInProducts(10)->getCollection();
        $recommended_products = $repo->recommendedProducts(10);
        $best_sellers = $repo->bestSellingProducts(10)->getCollection();
        $trending_products = $repo->trendingNowProducts(10);
        $featured_products = $repo->featuredProducts(10);

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
        ));
    }
}
