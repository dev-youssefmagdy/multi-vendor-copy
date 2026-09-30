<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Country;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class TodayChancesController extends PanelController
{
    public function index(Request $request, TenantPanelRepository $repository): View
    {
        $perPage = 12;
        $products = Product::query()
            ->with(['translations.language', 'files', 'categories.translations.language'])
            ->where('active', true)
            ->whereNotNull('cost_price')
            ->whereColumn('cost_price', '<', 'default_price')
            ->orderByRaw('(default_price - cost_price) / default_price DESC')
            ->paginate($perPage);

        $page       = $products->getCollection();
        $productIds = $page->pluck('id')->map(fn($id) => (int) $id)->all();

        // Precompute sales velocity + category saturation for the whole page at once.
        $context = $repository->opportunityContext($productIds);

        // Build a country lookup keyed by country_id for the markets field.
        $allCountryIds = $page
            ->flatMap(fn(Product $p) => (array) ($p->allowed_country_ids ?? []))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $countryMap = [];
        if (!empty($allCountryIds)) {
            $countryMap = tenancy()->central(fn() =>
                Country::query()
                    ->whereIn('id', $allCountryIds)
                    ->get(['id', 'iso2', 'name'])
                    ->mapWithKeys(fn(Country $c) => [
                        (int) $c->id => ['iso2' => (string) $c->iso2, 'name' => (string) $c->name],
                    ])
                    ->all()
            ) ?? [];
        }

        $items = $page->map(
            fn(Product $p) => $repository->buildOpportunityArray(
                $p,
                $context[$p->id] ?? null,
                $countryMap,
            )
        );

        return view('tenant.pages.todays-chances.index', [
            'products' => $products,
            'items'    => $items,
        ]);
    }
}
