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

final class ShippingAnalyticsController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repository)
    {
    }

    private function overview(): array
    {
        return Cache::remember('tenant:'.tenant('id').':shipping-analytics:overview', 60, fn () => $this->repository->shippingAnalyticsOverview());
    }

    public function index(): View
    {
        $overview = $this->overview();
        $statusRows = collect($overview['status_rows']);

        return view('tenant.pages.insights._layout', [
            'title' => 'Shipping Analytics',
            'badge' => 'Insights',
            'description' => 'Measure tenant shipping capture, fulfillment throughput, and recorded shipping payload data from tenant orders and order items.',
            'contentIntro' => 'Shipping values fall back to item-level shipping fees when an order-level shipping charge is not stored, keeping order billing and shipping analytics aligned.',
            'cardsGridClass' => 'g-stats4',
            'cards' => Metric::cards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Shipping Revenue', 'description' => 'Monthly shipping revenue captured from tenant orders.', 'canvas' => 'barChart'],
                        ['title' => 'Shipping Status Mix', 'description' => 'Distribution of pending, in-progress, shipped, and delivered orders.', 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Fulfillment Throughput', 'description' => 'Orders placed versus paid orders by month.', 'canvas' => 'lineChart'],
                        ['title' => 'Operational Notes', 'description' => 'Use these metrics to trace shipping capture against order flow.', 'metrics' => $statusRows->map(fn (array $row) => ['label' => $row['label'], 'value' => number_format($row['count'])])->values()->all()],
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
                    'id' => 'shipping-analytics-monthly',
                    'url' => route('tenant.analytics.shipping.data.monthly'),
                    'title' => 'Monthly Shipping Performance',
                    'description' => 'Monthly shipping capture and average shipping value per order.',
                    'columns' => [
                        ['data' => 'label', 'title' => 'Month', 'orderable' => false],
                        ['data' => 'orders', 'title' => 'Orders', 'orderable' => false],
                        ['data' => 'fulfilled', 'title' => 'Fulfilled', 'orderable' => false],
                        ['data' => 'shipping', 'title' => 'Shipping Fees', 'orderable' => false],
                        ['data' => 'average_shipping', 'title' => 'Average Shipping', 'orderable' => false],
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
            ->editColumn('shipping', fn (array $row) => '$'.number_format((float) $row['shipping'], 2))
            ->editColumn('average_shipping', fn (array $row) => '$'.number_format((float) $row['average_shipping'], 2))
            ->toJson();
    }
}
