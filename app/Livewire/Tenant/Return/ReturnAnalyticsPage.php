<?php

namespace App\Livewire\Tenant\Return;

use App\Enums\ReturnStatus;
use App\Livewire\Tenant\Base\ContentPage;
use App\Models\ReturnRequest;
use App\Models\Tenant\Product;
use Illuminate\Support\Carbon;

class ReturnAnalyticsPage extends ContentPage
{
    protected function pageView(): string
    {
        return 'livewire.tenant.return.return-analytics';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => __('Return Analytics'),
            'badge' => __('Store'),
            'description' => __('Return-request trends, reasons, and processing time for your store.'),
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->id;

        $base = ReturnRequest::query()->where('tenant_id', $tenantId);

        $total = (clone $base)->count();
        $last30 = (clone $base)->where('created_at', '>=', now()->subDays(30))->count();

        $approved = (clone $base)->whereIn('status', [ReturnStatus::Approved->value, ReturnStatus::Refunded->value])->count();
        $rejected = (clone $base)->where('status', ReturnStatus::Rejected->value)->count();

        $approvalRate = $total > 0 ? ($approved / $total) * 100 : 0;
        $rejectionRate = $total > 0 ? ($rejected / $total) * 100 : 0;

        $topReasons = (clone $base)
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topProductRows = (clone $base)
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as total')
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $productNames = Product::whereIn('id', $topProductRows->pluck('product_id'))
            ->get()
            ->mapWithKeys(fn (Product $p) => [$p->id => $p->translationValue('name') ?? $p->slug]);

        $avgProcessingHours = (clone $base)
            ->whereIn('status', [ReturnStatus::Approved->value, ReturnStatus::Rejected->value, ReturnStatus::Refunded->value])
            ->get()
            ->avg(fn (ReturnRequest $r) => $r->created_at?->diffInHours($r->updated_at)) ?? 0;

        $monthly = (clone $base)
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn (ReturnRequest $r) => $r->created_at?->format('Y-m'))
            ->map->count();

        $monthlyRows = collect(range(0, 5))
            ->map(fn ($i) => now()->subMonths(5 - $i)->format('Y-m'))
            ->map(fn ($month) => [
                '<div class="entity-title">' . Carbon::createFromFormat('Y-m', $month)->format('M Y') . '</div>',
                '<div class="entity-subtitle">' . (int) ($monthly[$month] ?? 0) . '</div>',
            ])
            ->all();

        return array_merge(parent::pageData(), [
            'cards' => $this->presentMetricCards([
                ['label' => __('Total Returns'), 'value' => $total, 'format' => 'number', 'caption' => __('All-time return requests'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Last 30 Days'), 'value' => $last30, 'format' => 'number', 'caption' => __('Returns submitted this month'), 'dot' => 'dot-blue', 'glow' => 'card-glow-blue'],
                ['label' => __('Approval Rate'), 'value' => $approvalRate, 'format' => 'percent', 'caption' => __('Approved or refunded'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Rejection Rate'), 'value' => $rejectionRate, 'format' => 'percent', 'caption' => __('Rejected requests'), 'dot' => 'dot-red', 'glow' => 'card-glow-violet'],
                ['label' => __('Avg Processing Time'), 'value' => $avgProcessingHours, 'format' => 'number', 'suffix' => 'hrs', 'caption' => __('From submission to resolution'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'cardsGridClass' => 'g-stats4',
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        [
                            'title' => __('Top 5 Return Reasons'),
                            'metrics' => $topReasons->map(fn ($row) => [
                                'label' => __($row->reason->label()),
                                'value' => (int) $row->total,
                            ])->all(),
                        ],
                        [
                            'title' => __('Top 5 Most Returned Products'),
                            'metrics' => $topProductRows->map(fn ($row) => [
                                'label' => $productNames[$row->product_id] ?? __('Product #:id', ['id' => $row->product_id]),
                                'value' => (int) $row->total,
                            ])->all(),
                        ],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => __('Monthly Trend (Last 6 Months)'),
                    'description' => __('Return requests submitted per month.'),
                    'headers' => [__('Month'), __('Return Requests')],
                    'rows' => $monthlyRows,
                ],
            ],
        ]);
    }
}
