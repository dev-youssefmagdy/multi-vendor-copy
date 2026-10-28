<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Tenant\CatalogController;
use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Setting;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Mobile\MobileBootstrapBuilder;
use App\Services\Mobile\MobileSectionCatalog;
use App\Services\Tenant\CustomerCountryResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /home/sections/{key}: data for one home section (same datasets as web HomePage). */
class HomeController extends CatalogController
{
    public function section(Request $request, string $key, MobileBootstrapBuilder $builder): JsonResponse
    {
        $meta = MobileSectionCatalog::META[$key] ?? null;
        ['theme_slug' => $slug, 'variant' => $variant] = $builder->resolveTheme();

        if (!$meta || !in_array($key, MobileSectionCatalog::allowedKeys($slug, $variant?->key ?? 'v1', $variant?->view), true)) {
            return response()->json(['success' => false, 'message' => 'Unknown section for the active app theme.', 'code' => 'section_not_found'], 404);
        }

        $limit = max(1, min(30, (int) $request->query('limit', 10)));
        $repo = app(StorefrontRepository::class);

        return response()->json([
            'success' => true,
            'data' => ['key' => $key, 'widget' => $meta[0]] + $this->dataFor($meta[1], $repo, $limit, (string) $request->query('tab', '')),
        ]);
    }

    private function products($collection): array
    {
        return collect($collection instanceof \Illuminate\Pagination\LengthAwarePaginator ? $collection->items() : $collection)
            ->map(fn ($p) => $this->transformProduct($p))->values()->all();
    }

    private function dataFor(string $source, StorefrontRepository $repo, int $limit, string $tab): array
    {
        switch ($source) {
            case 'static':
                return [];
            case 'banners':
                return ['banners' => $repo->activeBanners()->map(fn (Banner $b) => [
                    'id' => $b->id,
                    'image' => $b->image_path,
                    'link' => $b->url,
                    'title' => $b->translationValue('title'),
                    'subtitle' => $b->translationValue('subtitle'),
                    'button_text' => $b->translationValue('button_text'),
                ])->values()->all()];
            case 'categories':
                return ['categories' => $this->categories($repo)];
            case 'categories_new_in':
                return ['categories' => $this->categories($repo), 'products' => $this->products($repo->newInProducts($limit))];
            case 'buy_together':
                return ['categories' => $this->categories($repo), 'products' => $this->products($repo->randomActiveProducts(2))];
            case 'flash':
                return $this->flash($repo);
            case 'tabbed':
                return $this->tabbed($repo, $limit, $tab);
            case 'promo':
                $s = Setting::query()->where('name', 'like', 'promo_banner_%')->pluck('value', 'name');
                $promo = [
                    'image' => $s['promo_banner_image_url'] ?? null,
                    'mobile_image' => $s['promo_banner_mobile_image_url'] ?? null,
                    'link' => $s['promo_banner_link'] ?? null,
                    'title' => $s['promo_banner_title'] ?? null,
                    'subtitle' => $s['promo_banner_subtitle'] ?? null,
                    'cta_text' => $s['promo_banner_cta_text'] ?? null,
                ];

                return ['promo' => ($promo['image'] || $promo['title']) ? $promo : null];
            case 'trending':
                return ['products' => $this->products($repo->trendingNowProducts($limit))];
            case 'new_in':
                return ['products' => $this->products($repo->newInProducts($limit)), 'see_all' => 'new-in'];
            case 'featured':
                return ['products' => $this->products($repo->featuredProducts($limit))];
            case 'recommended':
                $items = $repo->recommendedProducts($limit);
                // Same fallback as the web (bestSellingIfNoRecommended).
                if ($items->isEmpty()) {
                    $items = collect($repo->bestSellingProducts($limit)->items());
                }

                return ['products' => $this->products($items)];
            case 'top_rated':
                $items = $repo->homeTopRatedProducts($limit);
                // Same fallback as the web (productsIfNoTopRated).
                if (count($items->items()) === 0) {
                    $items = $repo->homeProducts($limit);
                }

                return ['products' => $this->products($items), 'see_all' => 'top-rated'];
            case 'hot_deals':
                $flash = $repo->flashProductsByPrice();

                return ['products' => $this->products($flash->isNotEmpty() ? $flash->take($limit)->values() : $repo->featuredProducts($limit))];
            case 'best_selling':
                return ['products' => $this->products($repo->bestSellingProducts($limit)), 'see_all' => 'best-selling'];
            default: // products
                return ['products' => $this->products($repo->homeProducts($limit))];
        }
    }

    private function categories(StorefrontRepository $repo): array
    {
        return $repo->rootCategoriesWithChildren()->map(fn (Category $c) => $this->transformCategory($c))->values()->all();
    }

    private function flash(StorefrontRepository $repo): array
    {
        $sales = $repo->activeFlashSales();
        $couponCode = null;

        if ($sales->isNotEmpty()) {
            $couponCode = Coupon::query()
                ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->forCountry(app(CustomerCountryResolver::class)->resolveId())
                ->latest()->value('code');
        }

        return ['flash' => [
            'sales' => $sales->map(fn ($s) => [
                'id' => $s->id,
                'discount_percentage' => $s->discount_percentage,
                'ends_at' => $s->end_date?->toIso8601String(),
                'banner' => $s->banner_url,
            ])->values()->all(),
            'products' => $this->products($repo->flashProductsByPrice()->take(20)->values()),
            'banner' => $sales->first()?->banner_url,
            'coupon_code' => $couponCode,
            'ends_at' => $sales->map->end_date->filter()->sort()->first()?->toIso8601String(),
        ]];
    }

    private function tabbed(StorefrontRepository $repo, int $limit, string $tab): array
    {
        $tabs = ['cat-flash', 'cat-bestselling', 'cat-newin', 'cat-toprated'];
        $data = ['tabs' => $tabs, 'default_tab' => 'cat-flash'];

        if (!in_array($tab, $tabs, true)) {
            return $data;
        }

        $items = match ($tab) {
            'cat-flash' => $repo->flashProductsByPrice()->take($limit)->values(),
            'cat-bestselling' => $repo->bestSellingProducts($limit),
            'cat-newin' => $repo->newInProducts($limit),
            'cat-toprated' => $repo->homeTopRatedProducts($limit),
        };

        return $data + ['tab' => $tab, 'products' => $this->products($items)];
    }
}
