<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\ProductBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class BadgeSortController extends PanelController
{
    public function index(Request $request, ProductBadge $badge): View
    {
        $activeCountryId = $request->integer('country_id') ?: null;

        return view('tenant.pages.catalog.sort.badge-products', [
            'badge' => $badge,
            'activeCountryId' => $activeCountryId,
            'products' => $badge->productsForCountry($activeCountryId)->get(),
        ]);
    }

    public function update(Request $request, ProductBadge $badge): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $countryId = $request->integer('country_id') ?: null;

        foreach ($ids as $index => $productId) {
            DB::table('product_badge_product')
                ->where('product_badge_id', $badge->id)
                ->where('product_id', (int) $productId)
                ->when($countryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $countryId))
                ->update(['sort_order' => $index]);
        }

        return $this->success('Product order saved.');
    }
}
