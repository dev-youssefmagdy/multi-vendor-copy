<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Repositories\ProductRepository;
use App\Services\Tenant\CentralCatalogTenantSyncService;
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

        $selectedProductLabels = app(ProductRepository::class)->productNamesForIds($selectedProductIds);

        $initialProducts = app(ProductRepository::class)->searchForPicker('', 1);

        $categoryTree = $this->buildCategoryTree();

        return view('admin.badge.show', [
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
        $result = app(ProductRepository::class)->searchForPicker(
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

        $names = app(ProductRepository::class)->productNamesForIds($productIds);

        return response()->json([
            'productIds'    => $productIds,
            'productLabels' => $names,
            'message'       => count($productIds) . ' products available in that category.',
        ]);
    }

    public function save(Request $request, ProductBadge $badge, CentralCatalogTenantSyncService $sync): RedirectResponse
    {
        $ids = array_filter(array_map('intval', $request->input('product_ids', [])));

        $badge->products()->sync($ids);

        $sync->syncAllTenants(['badges']);

        return back()->with('status', 'Badge assignment saved — ' . count($ids) . ' products assigned. Tenant sync triggered.');
    }

    protected function buildCategoryTree(): array
    {
        $roots = \App\Models\Category::query()
            ->with([
                'translations.language',
                'children.translations.language',
                'children.children.translations.language',
                'children.children.children.translations.language',
            ])
            ->whereNull('parent_id')
            ->get();

        $flat = [];
        $walk = function ($items, int $depth) use (&$walk, &$flat) {
            foreach ($items as $item) {
                $flat[] = [
                    'id'    => (int) $item->id,
                    'name'  => (string) ($item->translationValue('name') ?? ('Category #' . $item->id)),
                    'depth' => $depth,
                ];
                if ($item->children && $item->children->count()) {
                    $walk($item->children, $depth + 1);
                }
            }
        };
        $walk($roots, 0);

        return $flat;
    }
}
