<?php

namespace App\Livewire\Tenant\Analytics;

use App\Livewire\Tenant\Base\ContentPage;
use App\Repositories\Tenant\TenantPanelRepository;

class CustomerLifetimeValuePage extends ContentPage
{
    protected function pageMeta(): array
    {
        $overview = app(TenantPanelRepository::class)->customerLifetimeOverview();

        return [
            'title' => __('Customer Lifetime Value'),
            'badge' => __('Insights'),
            'description' => __('Rank customer value, order frequency, and buyer mix using tenant customers joined with tenant orders.'),
            'contentIntro' => __('Lifetime spend is calculated from full tenant order totals, not just raw subtotals, so billing and CRM views stay aligned.'),
            'cardsGridClass' => 'g-stats4',
            'cards' => $this->presentMetricCards($overview['cards']),
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
                                ['label' => __('Top Customer'), 'value' => collect($overview['rows'])->first()['name'] ?? 'N/A'],
                                ['label' => __('Top Spend'), 'value' => '$' . number_format((float) (collect($overview['rows'])->first()['total'] ?? 0), 2)],
                                ['label' => __('Repeat Buyers'), 'value' => number_format((int) collect($overview['rows'])->where('orders', '>', 1)->count())],
                            ]
                        ],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => __('Customer Ranking'),
                    'description' => __('Customer lifetime value ranked by total spend and repeat order activity.'),
                    'headers' => [__('Customer'), __('Orders'), __('Paid Orders'), __('Total Spend'), __('Collected Spend'), __('Average Order'), __('Last Order')],
                    'rows' => collect($overview['rows'])->map(fn(array $row) => [
                        '<div class="entity-title">' . e($row['name']) . '</div><div class="entity-subtitle">' . e($row['email']) . '</div>',
                        e((string) $row['orders']),
                        e((string) $row['paid_orders']),
                        '$' . e(number_format((float) $row['total'], 2)),
                        '$' . e(number_format((float) $row['paid_total'], 2)),
                        '$' . e(number_format((float) $row['average'], 2)),
                        e(optional($row['last_order'])->format('M d, Y') ?? 'N/A'),
                    ])->all(),
                ]
            ],
        ];
    }
}
