<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\VendorSettlement;
use Livewire\WithPagination;

/**
 * Read-only view of settlement payments this tenant has made to central for
 * product cost / shipping on vendor-gateway orders. Mirrors the admin
 * VendorSettlementsPage but scoped to the current tenant only.
 */
class SettlementPaymentsPage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'paid';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Settlement Payments',
            'badge' => 'Finance',
            'actionLabel' => '',
            'description' => 'Review the settlement payments you have made to central for product cost and shipping.',
            'tableTitle' => 'Settlement Records',
            'headers' => ['Invoice', 'Order', 'Gateway', 'Transaction', 'Amount', 'Settled At'],
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->getTenantKey();

        $query = VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('order_uuid', 'like', "%{$this->search}%")
                        ->orWhere('transaction_id', 'like', "%{$this->search}%");
                });
            })
            ->latest('settled_at');

        $records = $query->paginate(20);

        $totalCount = VendorSettlement::query()->where('tenant_id', $tenantId)->where('status', 'paid')->count();
        $totalPaid = VendorSettlement::query()->where('tenant_id', $tenantId)->where('status', 'paid')->sum('total');
        $thisMonth = VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereMonth('settled_at', now()->month)
            ->whereYear('settled_at', now()->year)
            ->sum('total');

        $statuses = VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('status', 'status')
            ->filter()
            ->toArray();

        $rows = collect($records->items())->map(function (VendorSettlement $s) {
            return [
                '<div class="entity-title" style="font-family:monospace;font-size:12px;">' . e($s->invoice_number) . '</div>',
                '<div class="entity-title" style="font-family:monospace;font-size:11px;">' . e(str()->limit($s->order_uuid, 28)) . '</div>',
                '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $s->gateway_code ?? '—'))) . '</div>',
                $s->transaction_id
                ? '<div class="entity-subtitle" style="font-family:monospace;font-size:11px;">' . e(str()->limit($s->transaction_id, 28)) . '</div>'
                : '<span class="entity-subtitle">—</span>',
                '<strong>$' . number_format((float) $s->total, 2) . '</strong>'
                . '<div class="entity-subtitle">Fee: $' . number_format((float) $s->gateway_fee, 2) . '</div>',
                '<div class="entity-title">' . e($s->settled_at?->format('M d, Y')) . '</div>'
                . '<div class="entity-subtitle">' . e($s->settled_at?->format('H:i')) . '</div>',
            ];
        })->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice, order UUID, transaction…'],
                [
                    'label' => 'Status',
                    'model' => 'statusFilter',
                    'type' => 'select',
                    'options' => ['' => 'All Statuses'] + array_map(fn($s) => ucwords(str_replace('_', ' ', $s)), $statuses),
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Settlements', 'value' => $totalCount, 'format' => 'number', 'caption' => 'Settlement payments made to central.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Paid', 'value' => $totalPaid, 'format' => 'currency', 'caption' => 'Cumulative amount settled with central.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $thisMonth, 'format' => 'currency', 'caption' => 'Settlements paid this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'rows' => $rows,
            'tableDescription' => $records->total() . ' settlement records matched the current filters.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}
