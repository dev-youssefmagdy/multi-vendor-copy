<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
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

        $items = $products->getCollection()->map(
            fn(Product $p) => $repository->buildOpportunityArray($p)
        );

        return view('tenant.pages.todays-chances.index', [
            'products' => $products,
            'items'    => $items,
        ]);
    }
}
