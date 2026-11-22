<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ProductBadge;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Central admin "Sort — <badge>" (drag order of the products of one central badge, per country; NULL country = the
 * Default list). Blade + JSON + fetch() (dedup plan §7), replaces the Livewire `Admin\Product\SortBadgeProducts`.
 *
 * After a save the global CacheVersion tag `CentralProductBadge` is bumped (plan F1 / invariant 8): tenants with
 * `catalog_source.badges = central` read this order live through BadgeCatalog, so their home badge sections must
 * not keep serving the cached old order. Sync-mode tenants are unchanged: their copy keeps the tenant's own order
 * (the badges sync never overwrites an existing tenant sort_order), exactly as before.
 *
 * Route group middleware: `admin.permission:catalog.badges.manage`.
 */
class BadgeSortController extends Controller
{
    public function index(Request $request, ProductBadge $badge): View
    {
        $activeCountryId = $request->integer('country_id') ?: null;

        return view('admin.badge.sort', [
            'badge' => $badge,
            'badgeTitle' => ucwords(str_replace('-', ' ', $badge->text)),
            'activeCountryId' => $activeCountryId,
            'countries' => Country::query()->orderBy('name')->get(['id', 'iso2', 'name', 'flag_emoji']),
            'products' => $badge->productsForCountry($activeCountryId)->with('translations.language')->get(),
        ]);
    }

    public function update(Request $request, ProductBadge $badge): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['present', 'array', 'max:5000'],
            'ids.*' => ['integer'],
            'country_id' => ['nullable', 'integer'],
        ]);
        $countryId = ((int) ($data['country_id'] ?? 0)) ?: null;
        $db = $badge->getConnection();

        $db->transaction(function () use ($db, $badge, $countryId, $data) {
            foreach (array_values($data['ids']) as $index => $productId) {
                $db->table('product_badge_product')
                    ->where('product_badge_id', $badge->id)
                    ->where('product_id', (int) $productId)
                    ->when($countryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $countryId))
                    ->update(['sort_order' => $index]);
            }
        });

        CacheVersion::bumpMany([BadgeCatalog::CACHE_TAG]);

        return response()->json(['success' => true, 'message' => __('Product order saved.')]);
    }
}
