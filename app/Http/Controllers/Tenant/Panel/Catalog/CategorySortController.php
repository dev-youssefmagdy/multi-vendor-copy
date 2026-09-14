<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategorySortController extends PanelController
{
    public function index(): View
    {
        return view('tenant.pages.catalog.sort.categories', [
            'categories' => Category::query()
                ->with('translations.language')
                ->where('active', true)
                ->orderBy('order_number')
                ->get(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);

        foreach ($ids as $index => $categoryId) {
            Category::query()->whereKey((int) $categoryId)->update(['order_number' => $index]);
        }

        return $this->success('Category order saved.');
    }
}
