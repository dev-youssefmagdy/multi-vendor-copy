<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategoryProductsController extends PanelController
{
    public function index(Category $category): View
    {
        return view('tenant.pages.catalog.sort.category-products', [
            'category' => $category,
            'products' => $category->products()->get(),
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $ids = (array) $request->input('ids', []);

        foreach ($ids as $index => $productId) {
            $category->products()->updateExistingPivot((int) $productId, ['sort_order' => $index]);
        }

        return $this->success('Product order saved.');
    }
}
