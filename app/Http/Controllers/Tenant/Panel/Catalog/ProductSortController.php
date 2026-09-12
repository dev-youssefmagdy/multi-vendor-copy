<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductSortController extends PanelController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->with('translations.language')
            ->when(filled($search), function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('slug', 'like', "%{$search}%")
                        ->orWhereHas('translations', fn ($t) => $t->where('field', 'name')->where('value', 'like', "%{$search}%"));
                });
            })
            ->orderBy('order_number')
            ->get();

        return view('tenant.pages.catalog.sort.products', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);

        foreach ($ids as $index => $productId) {
            Product::query()->whereKey((int) $productId)->update(['order_number' => $index]);
        }

        return $this->success('Product order saved.');
    }
}
