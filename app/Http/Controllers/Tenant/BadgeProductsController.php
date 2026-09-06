<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BadgeProductsController extends Controller
{
    public function show(Request $request, ProductBadge $badge): \Illuminate\View\View
    {
        $badgeTitle = match ($badge->text) {
            'new-in'       => 'New In Products',
            'best-selling' => 'Best Selling Products',
            'featured'     => 'Featured Products',
            'recommended'  => 'Recommended Products',
            default        => ucwords(str_replace('-', ' ', $badge->text)) . ' Products',
        };

        $activeCountryId = $request->integer('country_id') ?: null;

        $tenantId = tenant()->getTenantKey();
        $countries = tenancy()->central(fn () => \App\Models\Country::query()
            ->whereIn('id', \App\Models\TenantCountry::where('tenant_id', $tenantId)->where('is_active', true)->pluck('country_id'))
            ->orderBy('name')
            ->get(['id', 'iso2', 'name', 'flag_emoji']));

        $selectedProductIds = $badge->productsForCountry($activeCountryId)->pluck('products.id')->map(fn ($id) => (int) $id)->all();

        $repo = app(TenantPanelRepository::class);
        $selectedProductLabels = $repo->productNamesForIds($selectedProductIds);
        $initialProducts = $repo->searchProducts('', 1);
        $categoryTree = $repo->categoryTreeOptions();

        return view('tenant.badge.show', [
            'badge'                 => $badge,
            'badgeTitle'            => $badgeTitle,
            'countries'             => $countries,
            'activeCountryId'       => $activeCountryId,
            'selectedProductIds'    => $selectedProductIds,
            'selectedProductLabels' => $selectedProductLabels,
            'initialProducts'       => $initialProducts,
            'categoryTree'          => $categoryTree,
            'assignedCount'         => count($selectedProductIds),
            'catalogCount'          => Product::query()->count(),
        ]);
    }

    public function searchProducts(Request $request, ProductBadge $badge): JsonResponse
    {
        $result = app(TenantPanelRepository::class)->searchProducts(
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
        );

        return response()->json($result);
    }

    public function assignCategory(Request $request, ProductBadge $badge): JsonResponse
    {
        $categoryId = (int) $request->input('category_id');

        if (! $categoryId) {
            return response()->json(['error' => 'Please choose a category first.'], 422);
        }

        $productIds = Product::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($productIds)) {
            return response()->json(['error' => 'No products found in that category.'], 422);
        }

        $names = app(TenantPanelRepository::class)->productNamesForIds($productIds);

        return response()->json([
            'productIds'    => $productIds,
            'productLabels' => $names,
            'message'       => count($productIds) . ' products available in that category.',
        ]);
    }

    public function save(Request $request, ProductBadge $badge): RedirectResponse
    {
        $countryId = $request->integer('country_id') ?: null;
        $ids = array_filter(array_map('intval', $request->input('product_ids', [])));

        $existingOrder = $badge->productsForCountry($countryId)->pluck('product_badge_product.sort_order', 'products.id');
        $nextOrder = $existingOrder->isEmpty() ? 0 : ($existingOrder->max() + 1);

        \DB::table('product_badge_product')
            ->where('product_badge_id', $badge->id)
            ->when($countryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $countryId))
            ->delete();

        $now = now();
        foreach ($ids as $id) {
            \DB::table('product_badge_product')->insert([
                'product_badge_id' => $badge->id,
                'product_id'       => $id,
                'country_id'       => $countryId,
                'sort_order'       => $existingOrder[$id] ?? $nextOrder++,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        $label = $countryId ? ('country #' . $countryId) : 'Default';

        return back()->with('status', 'Badge assignment saved — ' . count($ids) . " products assigned for {$label}.");
    }
}
