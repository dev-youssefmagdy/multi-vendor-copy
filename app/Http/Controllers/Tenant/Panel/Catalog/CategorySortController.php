<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Category;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategorySortController extends PanelController
{
    public function index(): View
    {
        return view('tenant.pages.catalog.sort.categories', [
            // Dedup phase 4: merged categories (central live + own) when categories = central.
            'categories' => \App\Repositories\Tenant\Catalog\CategoryCatalog::query()
                ->with('translations.language')
                ->where('active', true)
                ->orderBy('order_number')
                ->get(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);

        if (\App\Repositories\Tenant\Catalog\CategoryCatalog::enabled()) {
            app(\App\Repositories\Tenant\Catalog\CategoryAssignments::class)->reorder($ids); // overrides / own rows

            return $this->success(__('Category order saved.'));
        }

        foreach ($ids as $index => $categoryId) {
            Category::query()->whereKey((int) $categoryId)->update(['order_number' => $index]);
        }

        CacheVersion::bump('Category');

        return $this->success(__('Category order saved.'));
    }
}
