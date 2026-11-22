<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Country;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\TenantCountry;
use App\Repositories\Tenant\Catalog\BadgeAssignments;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\CatalogBadge;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Per-country product order of a badge. Dedup plan phase 2: in `badges = central` mode the merged list is read from
 * BadgeCatalog and the order is written by BadgeAssignments (central pairs → tenant sort override rows, tenant
 * attaches → their pivot row); sync mode unchanged.
 */
final class BadgeSortController extends PanelController
{
    public function __construct(private readonly BadgeCatalog $catalog, private readonly BadgeAssignments $assignments)
    {
    }

    private function badge(string $value): ProductBadge|CatalogBadge
    {
        if (BadgeCatalog::enabled()) {
            return $this->catalog->resolve($value) ?? abort(404);
        }

        return ProductBadge::query()->where('text', $value)->firstOrFail();
    }

    public function index(Request $request, string $badge): View
    {
        $badge = $this->badge($badge);
        $activeCountryId = $request->integer('country_id') ?: null;

        if ($badge instanceof CatalogBadge) {
            $ids = $this->catalog->productIds($badge, $activeCountryId);
            $products = Product::query()->whereIn('id', $ids)->get()
                ->sortBy(fn (Product $product) => array_search((int) $product->id, $ids, true))->values();
        } else {
            $products = $badge->productsForCountry($activeCountryId)->get();
        }

        // Same country list as the assignment page (BadgeController::show): the sort page shows per-country tabs.
        $tenantId = tenant()->getTenantKey();
        $countries = tenancy()->central(fn () => Country::query()
            ->whereIn('id', TenantCountry::where('tenant_id', $tenantId)->where('is_active', true)->pluck('country_id'))
            ->orderBy('name')
            ->get(['id', 'iso2', 'name', 'flag_emoji']));

        return view('tenant.pages.catalog.sort.badge-products', [
            'badge' => $badge,
            'activeCountryId' => $activeCountryId,
            'countries' => $countries,
            'products' => $products,
        ]);
    }

    public function update(Request $request, string $badge): JsonResponse
    {
        $badge = $this->badge($badge);
        $ids = (array) $request->input('ids', []);
        $countryId = $request->integer('country_id') ?: null;

        if ($badge instanceof CatalogBadge) {
            $this->assignments->saveSort($badge, $countryId, $ids);

            return $this->success(__('Product order saved.'));
        }

        foreach ($ids as $index => $productId) {
            DB::table('product_badge_product')
                ->where('product_badge_id', $badge->id)
                ->where('product_id', (int) $productId)
                ->when($countryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $countryId))
                ->update(['sort_order' => $index]);
        }

        CacheVersion::bumpMany(['ProductBadge', 'Product']);

        return $this->success(__('Product order saved.'));
    }
}
