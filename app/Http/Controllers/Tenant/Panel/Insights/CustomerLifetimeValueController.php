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

final class CustomerLifetimeValueController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repository)
    {
    }

    private function overview(): array
    {
        return Cache::remember('tenant:'.tenant('id').':clv:overview', 60, fn () => $this->repository->customerLifetimeOverview());
    }

    public function index(): View
    {
        $overview = $this->overview();
        $rows = collect($overview['rows']);
        $firstRow = $rows->first();

        return view('tenant.pages.insights._layout', [
            'title' => 'Customer Lifetime Value',
            'badge' => 'Insights',
            'description' => 'Rank customer value, order frequency, and buyer mix using tenant customers joined with tenant orders.',
            'contentIntro' => 'Lifetime spend is calculated from full tenant order totals, not just raw subtotals, so billing and CRM views stay aligned.',
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Top Customer Spend', 'description' => 'Highest lifetime value customers ranked by total spend.', 'canvas' => 'barChart'],
                        ['title' => 'Buyer Mix', 'description' => 'Active, inactive, one-time, and repeat buyer distribution.', 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Customer Momentum', 'description' => 'New customer growth compared with repeat buyer activity.', 'canvas' => 'lineChart'],
                        [
                            'title' => 'What to Watch',
                            'description' => 'Use repeat-buyer growth and lifetime averages to identify retention gaps.',
                            'metrics' => [
                                ['label' => 'Top Customer', 'value' => $firstRow['name'] ?? 'N/A'],
                                ['label' => 'Top Spend', 'value' => '$'.number_format((float) ($firstRow['total'] ?? 0), 2)],
                                ['label' => 'Repeat Buyers', 'value' => number_format((int) $rows->where('orders', '>', 1)->count())],
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
                    'id' => 'clv-ranking',
                    'url' => route('tenant.analytics.clv.data'),
                    'title' => 'Customer Ranking',
                    'description' => 'Customer lifetime value ranked by total spend and repeat order activity.',
                    'columns' => [
                        ['data' => 'customer', 'title' => 'Customer', 'orderable' => false],
                        ['data' => 'orders', 'title' => 'Orders', 'orderable' => false],
                        ['data' => 'paid_orders', 'title' => 'Paid Orders', 'orderable' => false],
                        ['data' => 'total', 'title' => 'Total Spend', 'orderable' => false],
                        ['data' => 'paid_total', 'title' => 'Collected Spend', 'orderable' => false],
                        ['data' => 'average', 'title' => 'Average Order', 'orderable' => false],
                        ['data' => 'last_order', 'title' => 'Last Order', 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $rows = collect($this->overview()['rows']);

        return DataTables::collection($rows)
            ->addColumn('customer', fn (array $row) => view('tenant.pages.insights._cols.customer', ['row' => $row])->render())
            ->editColumn('total', fn (array $row) => '$'.number_format((float) $row['total'], 2))
            ->editColumn('paid_total', fn (array $row) => '$'.number_format((float) $row['paid_total'], 2))
            ->editColumn('average', fn (array $row) => '$'.number_format((float) $row['average'], 2))
            ->editColumn('last_order', fn (array $row) => optional($row['last_order'])->format('M d, Y') ?? 'N/A')
            ->rawColumns(['customer'])
            ->toJson();
    }
}
