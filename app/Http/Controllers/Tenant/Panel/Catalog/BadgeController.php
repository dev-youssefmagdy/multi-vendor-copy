<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Country;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\TenantCountry;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class BadgeController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function show(Request $request, ProductBadge $badge): View
    {
        $badgeTitle = match ($badge->text) {
            'new-in' => 'New In Products',
            'best-selling' => 'Best Selling Products',
            'featured' => 'Featured Products',
            'recommended' => 'Recommended Products',
            'trending-now' => 'Trending Now Products',
            default => ucwords(str_replace('-', ' ', $badge->text)).' Products',
        };

        $activeCountryId = $request->integer('country_id') ?: null;

        $tenantId = tenant()->getTenantKey();
        $countries = tenancy()->central(fn () => Country::query()
            ->whereIn('id', TenantCountry::where('tenant_id', $tenantId)->where('is_active', true)->pluck('country_id'))
            ->orderBy('name')
            ->get(['id', 'iso2', 'name', 'flag_emoji']));

        $selectedProductIds = $badge->productsForCountry($activeCountryId)->pluck('products.id')->map(fn ($id) => (int) $id)->all();
        $selectedProductLabels = $this->repo->productNamesForIds($selectedProductIds);

        return view('tenant.pages.catalog.badges.show', [
            'badge' => $badge,
            'badgeTitle' => $badgeTitle,
            'countries' => $countries,
            'activeCountryId' => $activeCountryId,
            'selectedProductIds' => $selectedProductIds,
            'selectedProductLabels' => $selectedProductLabels,
            'categoryTree' => $this->repo->categoryTreeOptions(),
            'stats' => Metric::cards([
                ['label' => 'Selected', 'value' => count($selectedProductIds), 'format' => 'number', 'caption' => "Products currently selected for the {$badge->text} badge.", 'dot' => 'dot-cyan'],
                ['label' => 'Catalog Products', 'value' => Product::query()->count(), 'format' => 'number', 'caption' => 'Total products available to assign to this badge.', 'dot' => 'dot-green'],
            ]),
        ]);
    }

    public function searchProducts(Request $request, ProductBadge $badge): JsonResponse
    {
        $result = $this->repo->searchProducts(
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
        );

        return response()->json([
            'results' => collect($result['items'])->map(fn ($name, $id) => ['id' => $id, 'text' => $name])->values()->all(),
            'pagination' => ['more' => $result['has_more']],
        ]);
    }

    public function assignCategory(Request $request, ProductBadge $badge): JsonResponse
    {
        $categoryId = (int) $request->input('category_id');

        if (!$categoryId) {
            return $this->failure('Please choose a category first.');
        }

        $productIds = Product::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($productIds)) {
            return $this->failure('No products found in that category.');
        }

        $names = $this->repo->productNamesForIds($productIds);

        return response()->json([
            'success' => true,
            'productIds' => $productIds,
            'productLabels' => $names,
            'message' => count($productIds).' products available in that category.',
        ]);
    }

    public function save(Request $request, ProductBadge $badge): JsonResponse
    {
        $countryId = $request->integer('country_id') ?: null;
        $ids = array_filter(array_map('intval', $request->input('product_ids', [])));

        $existingOrder = $badge->productsForCountry($countryId)->pluck('product_badge_product.sort_order', 'products.id');
        $nextOrder = $existingOrder->isEmpty() ? 0 : ($existingOrder->max() + 1);

        DB::table('product_badge_product')
            ->where('product_badge_id', $badge->id)
            ->when($countryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $countryId))
            ->delete();

        $now = now();
        foreach ($ids as $id) {
            DB::table('product_badge_product')->insert([
                'product_badge_id' => $badge->id,
                'product_id' => $id,
                'country_id' => $countryId,
                'sort_order' => $existingOrder[$id] ?? $nextOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $label = $countryId ? ('country #'.$countryId) : 'Default';

        return $this->success('Badge assignment saved — '.count($ids)." products assigned for {$label}.");
    }
}
