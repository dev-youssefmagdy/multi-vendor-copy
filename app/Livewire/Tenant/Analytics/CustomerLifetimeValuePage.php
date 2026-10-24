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
            'title' => 'Customer Lifetime Value',
            'badge' => 'Insights',
            'description' => 'Rank customer value, order frequency, and buyer mix using tenant customers joined with tenant orders.',
            'contentIntro' => 'Lifetime spend is calculated from full tenant order totals, not just raw subtotals, so billing and CRM views stay aligned.',
            'cardsGridClass' => 'g-stats4',
            'cards' => $this->presentMetricCards($overview['cards']),
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
                                ['label' => 'Top Customer', 'value' => collect($overview['rows'])->first()['name'] ?? 'N/A'],
                                ['label' => 'Top Spend', 'value' => '$' . number_format((float) (collect($overview['rows'])->first()['total'] ?? 0), 2)],
                                ['label' => 'Repeat Buyers', 'value' => number_format((int) collect($overview['rows'])->where('orders', '>', 1)->count())],
                            ]
                        ],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => 'Customer Ranking',
                    'description' => 'Customer lifetime value ranked by total spend and repeat order activity.',
                    'headers' => ['Customer', 'Orders', 'Paid Orders', 'Total Spend', 'Collected Spend', 'Average Order', 'Last Order'],
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
