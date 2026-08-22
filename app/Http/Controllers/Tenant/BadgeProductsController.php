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
    public function show(ProductBadge $badge): \Illuminate\View\View
    {
        $badgeTitle = match ($badge->text) {
            'new-in'       => 'New In Products',
            'best-selling' => 'Best Selling Products',
            'featured'     => 'Featured Products',
            'recommended'  => 'Recommended Products',
            default        => ucwords(str_replace('-', ' ', $badge->text)) . ' Products',
        };

        $selectedProductIds = $badge->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all();

        $repo = app(TenantPanelRepository::class);
        $selectedProductLabels = $repo->productNamesForIds($selectedProductIds);
        $initialProducts = $repo->searchProducts('', 1);
        $categoryTree = $repo->categoryTreeOptions();

        return view('tenant.badge.show', [
            'badge'                 => $badge,
            'badgeTitle'            => $badgeTitle,
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
        $ids = array_filter(array_map('intval', $request->input('product_ids', [])));

        $existingOrder = $badge->products()->pluck('product_badge_product.sort_order', 'products.id');
        $nextOrder = $existingOrder->isEmpty() ? 0 : ($existingOrder->max() + 1);

        $syncData = [];
        foreach ($ids as $id) {
            $syncData[$id] = ['sort_order' => $existingOrder[$id] ?? $nextOrder++];
        }
        $badge->products()->sync($syncData);

        return back()->with('status', 'Badge assignment saved — ' . count($ids) . ' products assigned.');
    }
}
