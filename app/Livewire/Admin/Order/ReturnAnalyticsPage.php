<?php

namespace App\Livewire\Admin\Order;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\HasCsvExport;
use App\Models\ReturnRequest;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class ReturnAnalyticsPage extends ContentPage
{
    use HasCsvExport;

    public string $tenantFilter = '';

    protected function pageView(): string
    {
        return 'livewire.admin.order.return-analytics';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Return Analytics',
            'badge' => 'All Tenants',
            'description' => 'Return-request trends, reasons, and processing time across every tenant store.',
        ];
    }

    protected function pageData(): array
    {
        $base = ReturnRequest::query();

        if ($this->tenantFilter) {
            $base->where('tenant_id', $this->tenantFilter);
        }

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
            ->selectRaw('tenant_id, product_id, count(*) as total')
            ->groupBy('tenant_id', 'product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $productLabels = $this->resolveProductLabels($topProductRows);

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

        $tenantOptions = ['' => 'All Tenants'] + Tenant::query()->orderBy('name')->pluck('name', 'id')->all();

        return array_merge(parent::pageData(), [
            'secondaryActions' => [
                ['label' => 'Export CSV', 'method' => 'export'],
            ],
            'fieldGroups' => [
                [
                    'title' => 'Filter',
                    'description' => 'Drill into a specific tenant\'s return data.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Tenant', 'model' => 'tenantFilter', 'type' => 'select', 'options' => $tenantOptions],
                    ],
                ],
            ],
            'cards' => $this->presentMetricCards([
                ['label' => 'Total Returns', 'value' => $total, 'format' => 'number', 'caption' => 'All-time return requests', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Last 30 Days', 'value' => $last30, 'format' => 'number', 'caption' => 'Returns submitted this month', 'dot' => 'dot-blue', 'glow' => 'card-glow-cyan'],
                ['label' => 'Approval Rate', 'value' => $approvalRate, 'format' => 'percent', 'caption' => 'Approved or refunded', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Rejection Rate', 'value' => $rejectionRate, 'format' => 'percent', 'caption' => 'Rejected requests', 'dot' => 'dot-red', 'glow' => 'card-glow-violet'],
                ['label' => 'Avg Processing Time', 'value' => $avgProcessingHours, 'format' => 'number', 'suffix' => 'hrs', 'caption' => 'From submission to resolution', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'cardsGridClass' => 'g-stats4',
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        [
                            'title' => 'Top 5 Return Reasons',
                            'metrics' => $topReasons->map(fn ($row) => [
                                'label' => $row->reason->label(),
                                'value' => (int) $row->total,
                            ])->all(),
                        ],
                        [
                            'title' => 'Top 5 Most Returned Products',
                            'metrics' => $topProductRows->map(fn ($row) => [
                                'label' => $productLabels[$row->tenant_id . ':' . $row->product_id] ?? "Product #{$row->product_id}",
                                'value' => (int) $row->total,
                            ])->all(),
                        ],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => 'Monthly Trend (Last 6 Months)',
                    'description' => 'Return requests submitted per month.',
                    'headers' => ['Month', 'Return Requests'],
                    'rows' => $monthlyRows,
                ],
            ],
        ]);
    }

    /**
     * Resolve product display names for top-returned-product rows. Product names live in each
     * tenant's own database, so this initializes tenancy per distinct tenant represented in the
     * (small, top-5) result set rather than attempting a cross-tenant join.
     */
    private function resolveProductLabels(\Illuminate\Support\Collection $rows): array
    {
        $labels = [];

        foreach ($rows->groupBy('tenant_id') as $tenantId => $tenantRows) {
            $tenantModel = Tenant::find($tenantId);

            if (!$tenantModel) {
                continue;
            }

            tenancy()->initialize($tenantModel);

            $products = \App\Models\Tenant\Product::whereIn('id', $tenantRows->pluck('product_id'))->get();

            foreach ($products as $product) {
                $labels[$tenantId . ':' . $product->id] = ($product->translationValue('name') ?? $product->slug) . ' (' . ($tenantModel->name ?? $tenantId) . ')';
            }
        }

        return $labels;
    }

    protected function exportHeaders(): array
    {
        return ['Tenant', 'Order Number', 'Product ID', 'Reason', 'Status', 'Created At', 'Resolved At'];
    }

    protected function exportRows(): array
    {
        $query = ReturnRequest::query()->latest();

        if ($this->tenantFilter) {
            $query->where('tenant_id', $this->tenantFilter);
        }

        $records = $query->get();
        $tenantNames = Tenant::whereIn('id', $records->pluck('tenant_id')->unique())->pluck('name', 'id');

        return $records->map(fn (ReturnRequest $r) => [
            $tenantNames[$r->tenant_id] ?? $r->tenant_id,
            $r->order_number,
            $r->product_id,
            $r->reason->label(),
            $r->status->label(),
            $r->created_at?->format('Y-m-d H:i:s'),
            $r->reviewed_at?->format('Y-m-d H:i:s'),
        ])->all();
    }

    protected function exportFileName(): string
    {
        return 'return-analytics-' . now()->format('Y-m-d') . '.csv';
    }
}
