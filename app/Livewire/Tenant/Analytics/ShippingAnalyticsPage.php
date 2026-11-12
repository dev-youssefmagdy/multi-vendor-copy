<?php

namespace App\Livewire\Tenant\Analytics;

use App\Livewire\Tenant\Base\ContentPage;
use App\Repositories\Tenant\TenantPanelRepository;

class ShippingAnalyticsPage extends ContentPage
{
    protected function pageMeta(): array
    {
        $overview = app(TenantPanelRepository::class)->shippingAnalyticsOverview();

        return [
            'title' => __('Shipping Analytics'),
            'badge' => __('Insights'),
            'description' => __('Measure tenant shipping capture, fulfillment throughput, and recorded shipping payload data from tenant orders and order items.'),
            'contentIntro' => __('Shipping values fall back to item-level shipping fees when an order-level shipping charge is not stored, keeping order billing and shipping analytics aligned.'),
            'cardsGridClass' => 'g-stats4',
            'cards' => $this->presentMetricCards($overview['cards']),
            'chartPayload' => $overview['chart_payload'],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Shipping Revenue'), 'description' => __('Monthly shipping revenue captured from tenant orders.'), 'canvas' => 'barChart'],
                        ['title' => __('Shipping Status Mix'), 'description' => __('Distribution of pending, in-progress, shipped, and delivered orders.'), 'canvas' => 'donutChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => __('Fulfillment Throughput'), 'description' => __('Orders placed versus paid orders by month.'), 'canvas' => 'lineChart'],
                        ['title' => __('Operational Notes'), 'description' => __('Use these metrics to trace shipping capture against order flow.'), 'metrics' => collect($overview['status_rows'])->map(fn(array $row) => ['label' => $row['label'], 'value' => number_format($row['count'])])->all()],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => __('Monthly Shipping Performance'),
                    'description' => __('Monthly shipping capture and average shipping value per order.'),
                    'headers' => [__('Month'), __('Orders'), __('Fulfilled'), __('Shipping Fees'), __('Average Shipping')],
                    'rows' => collect($overview['monthly_rows'])->map(fn(array $row) => [
                        e($row['label']),
                        e((string) $row['orders']),
                        e((string) $row['fulfilled']),
                        '$' . e(number_format((float) $row['shipping'], 2)),
                        '$' . e(number_format((float) $row['average_shipping'], 2)),
                    ])->all(),
                ]
            ],
        ];
    }
}
