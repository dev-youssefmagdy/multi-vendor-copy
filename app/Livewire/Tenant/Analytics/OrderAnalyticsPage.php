<?php

namespace App\Livewire\Tenant\Analytics;

use App\Livewire\Tenant\Base\ContentPage;
use App\Repositories\Tenant\TenantPanelRepository;

class OrderAnalyticsPage extends ContentPage
{
    protected function pageMeta(): array
    {
        $overview = app(TenantPanelRepository::class)->orderAnalyticsOverview();

        return [
            'title' => 'Order Analytics',
            'badge' => 'Insights',
            'description' => 'Analyze tenant order value, monthly collection performance, and queue mix directly from the tenant order tables.',
            'contentIntro' => 'Gross value, collected value, order counts, and customer momentum are all aggregated from tenant orders and related customers.',
            'cardsGridClass' => 'g-stats4',
            'cards' => $this->presentMetricCards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Gross vs Collected', 'description' => 'Monthly gross order value compared with paid collections.', 'canvas' => 'revenueChart'],
                        ['title' => 'Status Mix', 'description' => 'Live order pipeline distribution across tenant statuses.', 'canvas' => 'donutChart', 'legend' => collect($overview['status_rows'])->map(fn(array $row, int $index) => ['label' => $row['label'], 'value' => number_format($row['count']), 'dot' => ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'][$index % 4]])->all()],
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
            'tableSections' => [
                [
                    'title' => 'Monthly Performance',
                    'description' => 'Monthly order, payment, and average order performance for the tenant.',
                    'headers' => ['Month', 'Orders', 'Paid', 'Gross', 'Collected', 'Average Order'],
                    'rows' => collect($overview['monthly_rows'])->map(fn(array $row) => [
                        e($row['label']),
                        e((string) $row['orders']),
                        e((string) $row['paid_orders']),
                        '$' . e(number_format((float) $row['gross'], 2)),
                        '$' . e(number_format((float) $row['collected'], 2)),
                        '$' . e(number_format((float) $row['average'], 2)),
                    ])->all(),
                ],
                [
                    'title' => 'Status Breakdown',
                    'description' => 'Current order queue mix across statuses.',
                    'headers' => ['Status', 'Orders'],
                    'rows' => collect($overview['status_rows'])->map(fn(array $row) => [e($row['label']), e((string) $row['count'])])->all(),
                ],
            ],
            'bullets' => [
                'Gross sales include unpaid orders; collected values include paid orders only.',
                'Queue mix updates from the same order status values used in the tenant order list.',
                'Monthly customer momentum is included so order growth can be read alongside buyer growth.',
            ],
        ];
    }
}
