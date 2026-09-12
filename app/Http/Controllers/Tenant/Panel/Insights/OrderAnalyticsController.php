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

final class OrderAnalyticsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repository)
    {
    }

    private function overview(): array
    {
        return Cache::remember('tenant:'.tenant('id').':order-analytics:overview', 60, fn () => $this->repository->orderAnalyticsOverview());
    }

    public function index(): View
    {
        $overview = $this->overview();

        $statusRows = collect($overview['status_rows']);

        return view('tenant.pages.insights._layout', [
            'title' => 'Order Analytics',
            'badge' => 'Insights',
            'description' => 'Analyze tenant order value, monthly collection performance, and queue mix directly from the tenant order tables.',
            'contentIntro' => 'Gross value, collected value, order counts, and customer momentum are all aggregated from tenant orders and related customers.',
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Gross vs Collected', 'description' => 'Monthly gross order value compared with paid collections.', 'canvas' => 'revenueChart'],
                        ['title' => 'Status Mix', 'description' => 'Live order pipeline distribution across tenant statuses.', 'canvas' => 'donutChart', 'legend' => $statusRows->map(fn (array $row, int $index) => ['label' => $row['label'], 'value' => number_format($row['count']), 'dot' => ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'][$index % 4]])->values()->all()],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Order Volume', 'description' => 'Orders and paid orders by month.', 'canvas' => 'barChart'],
                        ['title' => 'Customer Momentum', 'description' => 'New customer creation versus repeat buying activity.', 'canvas' => 'lineChart'],
                    ],
                ],
            ],
            'bullets' => [
                'Gross sales include unpaid orders; collected values include paid orders only.',
                'Queue mix updates from the same order status values used in the tenant order list.',
                'Monthly customer momentum is included so order growth can be read alongside buyer growth.',
            ],
            'tables' => [
                [
                    'id' => 'order-analytics-monthly',
                    'url' => route('tenant.analytics.orders.data.monthly'),
                    'title' => 'Monthly Performance',
                    'description' => 'Monthly order, payment, and average order performance for the tenant.',
                    'columns' => [
                        ['data' => 'label', 'title' => 'Month', 'orderable' => false],
                        ['data' => 'orders', 'title' => 'Orders', 'orderable' => false],
                        ['data' => 'paid_orders', 'title' => 'Paid', 'orderable' => false],
                        ['data' => 'gross', 'title' => 'Gross', 'orderable' => false],
                        ['data' => 'collected', 'title' => 'Collected', 'orderable' => false],
                        ['data' => 'average', 'title' => 'Average Order', 'orderable' => false],
                    ],
                    'order' => [],
                ],
                [
                    'id' => 'order-analytics-status',
                    'url' => route('tenant.analytics.orders.data.status'),
                    'title' => 'Status Breakdown',
                    'description' => 'Current order queue mix across statuses.',
                    'columns' => [
                        ['data' => 'label', 'title' => 'Status', 'orderable' => false],
                        ['data' => 'count', 'title' => 'Orders', 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ]);
    }

    public function dataMonthly(): JsonResponse
    {
        $rows = collect($this->overview()['monthly_rows']);

        return DataTables::collection($rows)
            ->editColumn('orders', fn (array $row) => number_format($row['orders']))
            ->editColumn('paid_orders', fn (array $row) => number_format($row['paid_orders']))
            ->editColumn('gross', fn (array $row) => '$'.number_format((float) $row['gross'], 2))
            ->editColumn('collected', fn (array $row) => '$'.number_format((float) $row['collected'], 2))
            ->editColumn('average', fn (array $row) => '$'.number_format((float) $row['average'], 2))
            ->toJson();
    }

    public function dataStatus(): JsonResponse
    {
        $rows = collect($this->overview()['status_rows']);

        return DataTables::collection($rows)
            ->editColumn('count', fn (array $row) => number_format($row['count']))
            ->toJson();
    }
}
