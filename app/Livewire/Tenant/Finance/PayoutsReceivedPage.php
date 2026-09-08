<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\TenantPayout;
use Livewire\WithPagination;

/**
 * Read-only view of payouts this tenant has received from central for its
 * net order share collected through central-owned gateways. Mirrors the
 * admin PayoutsIndexPage but scoped to the current tenant only.
 */
class PayoutsReceivedPage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payouts Received',
            'badge' => 'Finance',
            'actionLabel' => '',
            'description' => 'Review the payouts you have received from central for your net order share.',
            'tableTitle' => 'Payout Records',
            'headers' => ['Invoice', 'Method', 'Amount', 'Status', 'Paid At'],
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->getTenantKey();

        $query = TenantPayout::query()
            ->where('tenant_id', $tenantId)
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('transaction_reference', 'like', "%{$this->search}%");
                });
            })
            ->latest('paid_at');

        $records = $query->paginate(20);

        $totalCount = TenantPayout::query()->where('tenant_id', $tenantId)->where('status', 'paid')->count();
        $totalReceived = TenantPayout::query()->where('tenant_id', $tenantId)->where('status', 'paid')->sum('amount');
        $thisMonth = TenantPayout::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $statuses = TenantPayout::query()
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('status', 'status')
            ->filter()
            ->toArray();

        $rows = collect($records->items())->map(function (TenantPayout $p) {
            $statusClass = match ($p->status) {
                'paid' => 'badge-green',
                'pending' => 'badge-amber',
                'cancelled' => 'badge-red',
                default => 'badge-cyan',
            };

            return [
                '<div class="entity-title" style="font-family:monospace;font-size:12px;">' . e($p->invoice_number) . '</div>',
                '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $p->method ?? '—'))) . '</div>',
                '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
                '<span class="status-pill ' . $statusClass . '">' . e(ucwords($p->status)) . '</span>',
                '<div class="entity-title">' . e($p->paid_at?->format('M d, Y')) . '</div>'
                . '<div class="entity-subtitle">' . e($p->paid_at?->format('H:i')) . '</div>',
            ];
        })->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice, transaction…'],
                [
                    'label' => 'Status',
                    'model' => 'statusFilter',
                    'type' => 'select',
                    'options' => ['' => 'All Statuses'] + array_map(fn($s) => ucwords($s), $statuses),
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Payouts', 'value' => $totalCount, 'format' => 'number', 'caption' => 'Payouts received from central.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Received', 'value' => $totalReceived, 'format' => 'currency', 'caption' => 'Cumulative amount received from central.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $thisMonth, 'format' => 'currency', 'caption' => 'Payouts received this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'rows' => $rows,
            'tableDescription' => $records->total() . ' payout records matched the current filters.',
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
