<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
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
        return Cache::driver('file')->remember('tenant:'.tenant('id').':order-analytics:overview', 60, fn () => $this->repository->orderAnalyticsOverview());
    }

    /** Data for this module's tab on the single Analytics page (AnalyticsController). */
    public function viewData(): array
    {
        $overview = $this->overview();

        $statusRows = collect($overview['status_rows']);

        return [
            'title' => __('Order Analytics'),
            'badge' => __('Insights'),
            'description' => __('Analyze tenant order value, monthly collection performance, and queue mix directly from the tenant order tables.'),
            'contentIntro' => __('Gross value, collected value, order counts, and customer momentum are all aggregated from tenant orders and related customers.'),
            'statusRows' => $statusRows->values()->all(),
            'monthlyRows' => collect($overview['monthly_rows'])->values()->all(),
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Gross vs Collected'), 'description' => __('Monthly gross order value compared with paid collections.'), 'canvas' => 'revenueChart'],
                        ['title' => __('Status Mix'), 'description' => __('Live order pipeline distribution across tenant statuses.'), 'canvas' => 'donutChart', 'legend' => $statusRows->map(fn (array $row, int $index) => ['label' => $row['label'], 'value' => number_format($row['count']), 'dot' => ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'][$index % 4]])->values()->all()],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Order Volume'), 'description' => __('Orders and paid orders by month.'), 'canvas' => 'barChart'],
                        ['title' => __('Customer Momentum'), 'description' => __('New customer creation versus repeat buying activity.'), 'canvas' => 'lineChart'],
                    ],
                ],
            ],
            'bullets' => [
                __('Gross sales include unpaid orders; collected values include paid orders only.'),
                __('Queue mix updates from the same order status values used in the tenant order list.'),
                __('Monthly customer momentum is included so order growth can be read alongside buyer growth.'),
            ],
            'tables' => [
                [
                    'id' => 'order-analytics-monthly',
                    'url' => route('tenant.analytics.orders.data.monthly'),
                    'title' => __('Monthly Performance'),
                    'description' => __('Monthly order, payment, and average order performance for the tenant.'),
                    'columns' => [
                        ['data' => 'label', 'title' => __('Month'), 'orderable' => false],
                        ['data' => 'orders', 'title' => __('Orders'), 'orderable' => false],
                        ['data' => 'paid_orders', 'title' => __('Paid'), 'orderable' => false],
                        ['data' => 'gross', 'title' => __('Gross'), 'orderable' => false],
                        ['data' => 'collected', 'title' => __('Collected'), 'orderable' => false],
                        ['data' => 'average', 'title' => __('Average Order'), 'orderable' => false],
                    ],
                    'order' => [],
                ],
                [
                    'id' => 'order-analytics-status',
                    'url' => route('tenant.analytics.orders.data.status'),
                    'title' => __('Status Breakdown'),
                    'description' => __('Current order queue mix across statuses.'),
                    'columns' => [
                        ['data' => 'label', 'title' => __('Status'), 'orderable' => false],
                        ['data' => 'count', 'title' => __('Orders'), 'orderable' => false],
                    ],
                    'order' => [],
                ],
            ],
        ];
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
