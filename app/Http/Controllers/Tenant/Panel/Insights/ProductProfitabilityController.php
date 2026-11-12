<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

final class ProductProfitabilityController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repository)
    {
    }

    private function overview(): array
    {
        return Cache::driver('file')->remember('tenant:'.tenant('id').':profitability:overview', 60, fn () => $this->repository->productProfitabilityOverview());
    }

    private function rows(): array
    {
        return Cache::driver('file')->remember('tenant:'.tenant('id').':profitability:rows', 60, fn () => $this->repository->profitabilityRows());
    }

    /** Data for this module's tab on the single Analytics page (AnalyticsController). */
    public function viewData(): array
    {
        $overview = $this->overview();
        $rows = collect($this->rows());
        $firstRow = $rows->first();

        return [
            'title' => __('Product Profitability'),
            'badge' => __('Insights'),
            'description' => __('Compare tenant catalog pricing against recorded sell-through, cost, and gross profit using product variants plus order items.'),
            'contentIntro' => __('Profitability combines catalog real price and sell price with actual order-item revenue, so static margins and realized margins can be compared together.'),
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Top Gross Profit Items'), 'description' => __('Highest contributors ranked by realized gross profit.'), 'canvas' => 'barChart'],
                        ['title' => __('Margin Bands'), 'description' => __('Distribution of tracked items by realized margin band.'), 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Revenue vs Profit'), 'description' => __('Monthly product revenue against monthly gross profit.'), 'canvas' => 'lineChart'],
                        [
                            'title' => __('Profitability Notes'),
                            'description' => __('Use sold quantity, revenue, and realized margin together before adjusting catalog pricing.'),
                            'metrics' => [
                                ['label' => __('Tracked Rows'), 'value' => number_format($rows->count())],
                                ['label' => __('Top Item'), 'value' => $firstRow['name'] ?? 'N/A'],
                                ['label' => __('Top Margin'), 'value' => isset($firstRow['margin']) ? number_format((float) $firstRow['margin'], 2).'%' : '0.00%'],
                            ],
                        ],
                    ],
                ],
            ],
            'bullets' => [
                __('Keep all data scoped to the current tenant database.'),
                __('Use repository-backed widgets and service-backed saves.'),
                __('Support both dark and light themes with the shared shell.'),
            ],
            'tables' => [
                [
                    'id' => 'profitability-ranking',
                    'url' => route('tenant.analytics.profitability.data'),
                    'title' => __('Profitability Ranking'),
                    'description' => __('Realized sales, cost, and gross profit across tracked products and variants.'),
                    'columns' => [
                        ['data' => 'name', 'title' => __('Product'), 'orderable' => false],
                        ['data' => 'real_price', 'title' => __('Real Price'), 'orderable' => false],
                        ['data' => 'sell_price', 'title' => __('Sell Price'), 'orderable' => false],
                        ['data' => 'sold_qty', 'title' => __('Units'), 'orderable' => false],
                        ['data' => 'revenue', 'title' => __('Revenue'), 'orderable' => false],
                        ['data' => 'cost', 'title' => __('Cost'), 'orderable' => false],
                        ['data' => 'profit', 'title' => __('Gross Profit'), 'orderable' => false],
                        ['data' => 'margin', 'title' => __('Margin'), 'orderable' => false],
                        ['data' => 'status', 'title' => __('Status'), 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ];
    }

    public function data(): JsonResponse
    {
        $rows = collect($this->rows());

        return DataTables::collection($rows)
            ->editColumn('real_price', fn (array $row) => '$'.number_format((float) $row['real_price'], 2))
            ->editColumn('sell_price', fn (array $row) => '$'.number_format((float) $row['sell_price'], 2))
            ->editColumn('revenue', fn (array $row) => '$'.number_format((float) $row['revenue'], 2))
            ->editColumn('cost', fn (array $row) => '$'.number_format((float) $row['cost'], 2))
            ->editColumn('profit', fn (array $row) => '$'.number_format((float) $row['profit'], 2))
            ->editColumn('margin', fn (array $row) => number_format((float) $row['margin'], 2).'%')
            ->editColumn('status', fn (array $row) => view('tenant::components.status-badge', ['status' => $row['status'], 'map' => ['active' => 'green', 'inactive' => 'red']])->render())
            ->rawColumns(['status'])
            ->toJson();
    }
}
