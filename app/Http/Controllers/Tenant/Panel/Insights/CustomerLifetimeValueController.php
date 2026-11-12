<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

final class CustomerLifetimeValueController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repository)
    {
    }

    private function overview(): array
    {
        return Cache::driver('file')->remember('tenant:' . tenant('id') . ':clv:overview', 60, fn() => $this->repository->customerLifetimeOverview());
    }

    /** Data for this module's tab on the single Analytics page (AnalyticsController). */
    public function viewData(): array
    {
        $overview = $this->overview();
        $rows = collect($overview['rows']);
        $firstRow = $rows->first();

        return [
            'title' => __('Customer Lifetime Value'),
            'badge' => __('Insights'),
            'description' => __('Rank customer value, order frequency, and buyer mix using tenant customers joined with tenant orders.'),
            'contentIntro' => __('Lifetime spend is calculated from full tenant order totals, not just raw subtotals, so billing and CRM views stay aligned.'),
            // Top customers for the server-rendered spend + ranking tables on the Analytics page
            'rankingRows' => $rows->take(10)->values()->all(),
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Top Customer Spend'), 'description' => __('Highest lifetime value customers ranked by total spend.'), 'canvas' => 'barChart'],
                        ['title' => __('Buyer Mix'), 'description' => __('Active, inactive, one-time, and repeat buyer distribution.'), 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Customer Momentum'), 'description' => __('New customer growth compared with repeat buyer activity.'), 'canvas' => 'lineChart'],
                        [
                            'title' => __('What to Watch'),
                            'description' => __('Use repeat-buyer growth and lifetime averages to identify retention gaps.'),
                            'metrics' => [
                                ['label' => __('Top Customer'), 'value' => $firstRow['name'] ?? 'N/A'],
                                ['label' => __('Top Spend'), 'value' => '$' . number_format((float) ($firstRow['total'] ?? 0), 2)],
                                ['label' => __('Repeat Buyers'), 'value' => number_format((int) $rows->where('orders', '>', 1)->count())],
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
                    'id' => 'clv-ranking',
                    'url' => route('tenant.analytics.clv.data'),
                    'title' => __('Customer Ranking'),
                    'description' => __('Customer lifetime value ranked by total spend and repeat order activity.'),
                    'columns' => [
                        ['data' => 'customer', 'title' => __('Customer'), 'orderable' => false],
                        ['data' => 'orders', 'title' => __('Orders'), 'orderable' => false],
                        ['data' => 'paid_orders', 'title' => __('Paid Orders'), 'orderable' => false],
                        ['data' => 'total', 'title' => __('Total Spend'), 'orderable' => false],
                        ['data' => 'paid_total', 'title' => __('Collected Spend'), 'orderable' => false],
                        ['data' => 'average', 'title' => __('Average Order'), 'orderable' => false],
                        ['data' => 'last_order', 'title' => __('Last Order'), 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ];
    }

    public function data(): JsonResponse
    {
        $rows = collect($this->overview()['rows']);

        return DataTables::collection($rows)
            ->addColumn('customer', fn(array $row) => view('tenant.pages.insights._cols.customer', ['row' => $row])->render())
            ->editColumn('total', fn(array $row) => '$' . number_format((float) $row['total'], 2))
            ->editColumn('paid_total', fn(array $row) => '$' . number_format((float) $row['paid_total'], 2))
            ->editColumn('average', fn(array $row) => '$' . number_format((float) $row['average'], 2))
            ->editColumn('last_order', fn(array $row) => optional($row['last_order'])->format('M d, Y') ?? 'N/A')
            ->rawColumns(['customer'])
            ->toJson();
    }
}
