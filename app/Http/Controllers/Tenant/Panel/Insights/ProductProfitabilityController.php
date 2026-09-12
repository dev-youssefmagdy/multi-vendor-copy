<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Contracts\View\View;
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
        return Cache::remember('tenant:'.tenant('id').':profitability:overview', 60, fn () => $this->repository->productProfitabilityOverview());
    }

    private function rows(): array
    {
        return Cache::remember('tenant:'.tenant('id').':profitability:rows', 60, fn () => $this->repository->profitabilityRows());
    }

    public function index(): View
    {
        $overview = $this->overview();
        $rows = collect($this->rows());
        $firstRow = $rows->first();

        return view('tenant.pages.insights._layout', [
            'title' => 'Product Profitability',
            'badge' => 'Insights',
            'description' => 'Compare tenant catalog pricing against recorded sell-through, cost, and gross profit using product variants plus order items.',
            'contentIntro' => 'Profitability combines catalog real price and sell price with actual order-item revenue, so static margins and realized margins can be compared together.',
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Top Gross Profit Items', 'description' => 'Highest contributors ranked by realized gross profit.', 'canvas' => 'barChart'],
                        ['title' => 'Margin Bands', 'description' => 'Distribution of tracked items by realized margin band.', 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Revenue vs Profit', 'description' => 'Monthly product revenue against monthly gross profit.', 'canvas' => 'lineChart'],
                        [
                            'title' => 'Profitability Notes',
                            'description' => 'Use sold quantity, revenue, and realized margin together before adjusting catalog pricing.',
                            'metrics' => [
                                ['label' => 'Tracked Rows', 'value' => number_format($rows->count())],
                                ['label' => 'Top Item', 'value' => $firstRow['name'] ?? 'N/A'],
                                ['label' => 'Top Margin', 'value' => isset($firstRow['margin']) ? number_format((float) $firstRow['margin'], 2).'%' : '0.00%'],
                            ],
                        ],
                    ],
                ],
            ],
            'bullets' => [
                'Keep all data scoped to the current tenant database.',
                'Use repository-backed widgets and service-backed saves.',
                'Support both dark and light themes with the shared shell.',
            ],
            'tables' => [
                [
                    'id' => 'profitability-ranking',
                    'url' => route('tenant.analytics.profitability.data'),
                    'title' => 'Profitability Ranking',
                    'description' => 'Realized sales, cost, and gross profit across tracked products and variants.',
                    'columns' => [
                        ['data' => 'name', 'title' => 'Product', 'orderable' => false],
                        ['data' => 'real_price', 'title' => 'Real Price', 'orderable' => false],
                        ['data' => 'sell_price', 'title' => 'Sell Price', 'orderable' => false],
                        ['data' => 'sold_qty', 'title' => 'Units', 'orderable' => false],
                        ['data' => 'revenue', 'title' => 'Revenue', 'orderable' => false],
                        ['data' => 'cost', 'title' => 'Cost', 'orderable' => false],
                        ['data' => 'profit', 'title' => 'Gross Profit', 'orderable' => false],
                        ['data' => 'margin', 'title' => 'Margin', 'orderable' => false],
                        ['data' => 'status', 'title' => 'Status', 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ]);
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
