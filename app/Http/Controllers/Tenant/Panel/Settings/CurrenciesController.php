<?php

declare(strict_types=1);

// ROUTES:
// Route::get('/currencies/data', [CurrenciesController::class, 'data'])->name('currencies.data');
// Route::patch('/currencies/{currency}/active', [CurrenciesController::class, 'toggleActive'])->name('currencies.toggle-active');
// Route::post('/currencies/{currency}/default', [CurrenciesController::class, 'makeDefault'])->name('currencies.default');
// (all under the existing tenant.permission:settings.regional.manage middleware, next to the kept `currencies` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Currency;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class CurrenciesController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $currencies = $this->repo->currencies();

        return view('tenant.pages.settings.currencies.index', [
            'stats' => Metric::cards([
                ['label' => 'Currencies', 'value' => $currencies->count(), 'format' => 'number', 'caption' => 'Currencies synced into this tenant', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $currencies->where('is_active', true)->count(), 'format' => 'number', 'caption' => 'Currencies available on storefront', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Default', 'value' => optional($currencies->firstWhere('is_default', true))->code ?? '-', 'caption' => 'Current storefront settlement currency', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('currency', 'Currency')->orderable(false),
                TableColumn::make('rate', 'Rate')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false)->searchable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);
        $query = $this->repo->queryCurrencies($filters);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('currency', fn (Currency $currency) => e($currency->code.' - '.$currency->name))
            ->editColumn('rate', fn (Currency $currency) => e($currency->symbol ?: '$').' '.e(number_format((float) $currency->conversion_rate, 6)))
            ->addColumn('status', fn (Currency $currency) => view('tenant.pages.settings.currencies._cols.status', ['currency' => $currency])->render())
            ->addColumn('actions', fn (Currency $currency) => view('tenant.pages.settings.currencies._cols.actions', ['currency' => $currency])->render())
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }

    public function toggleActive(Request $request, Currency $currency): JsonResponse
    {
        $currency->update(['is_active' => $request->boolean('active')]);

        return $this->success('Currency state updated successfully.');
    }

    public function makeDefault(Currency $currency): JsonResponse
    {
        $this->service->markDefaultCurrency($currency);

        return $this->success('Default currency updated successfully.');
    }
}
