<?php

namespace App\Livewire\Tenant\Analytics;

use App\Livewire\Tenant\Base\ContentPage;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\WithPagination;

class ProductProfitabilityPage extends ContentPage
{
    use WithPagination;

    protected function pageMeta(): array
    {
        $repo = app(TenantPanelRepository::class);
        $overview = $repo->productProfitabilityOverview();
        $paginator = $repo->paginateProductProfitabilityRows(20, 'profitability');
        $firstRow = collect($paginator->items())->first();

        return [
            'title' => 'Product Profitability',
            'badge' => 'Insights',
            'description' => 'Compare tenant catalog pricing against recorded sell-through, cost, and gross profit using product variants plus order items.',
            'contentIntro' => 'Profitability combines catalog real price and sell price with actual order-item revenue, so static margins and realized margins can be compared together.',
            'cardsGridClass' => 'g-stats4',
            'cards' => $this->presentMetricCards($overview['cards']),
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
                                ['label' => 'Tracked Rows', 'value' => number_format($paginator->total())],
                                ['label' => 'Top Item', 'value' => $firstRow['name'] ?? 'N/A'],
                                ['label' => 'Top Margin', 'value' => isset($firstRow['margin']) ? number_format((float) $firstRow['margin'], 2) . '%' : '0.00%'],
                            ]
                        ],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => 'Profitability Ranking',
                    'description' => 'Realized sales, cost, and gross profit across tracked products and variants.',
                    'headers' => ['Product', 'Real Price', 'Sell Price', 'Units', 'Revenue', 'Cost', 'Gross Profit', 'Margin', 'Status'],
                    'rows' => collect($paginator->items())->map(fn(array $row) => [
                        e($row['name']),
                        '$' . e(number_format((float) $row['real_price'], 2)),
                        '$' . e(number_format((float) $row['sell_price'], 2)),
                        e((string) $row['sold_qty']),
                        '$' . e(number_format((float) $row['revenue'], 2)),
                        '$' . e(number_format((float) $row['cost'], 2)),
                        '$' . e(number_format((float) $row['profit'], 2)),
                        e(number_format((float) $row['margin'], 2)) . '%',
                        e($row['status']),
                    ])->all(),
                    'paginator' => $paginator,
                ]
            ],
        ];
    }
}
