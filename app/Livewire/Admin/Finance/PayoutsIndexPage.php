<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Base\ListPage;
use App\Models\Tenant;
use App\Models\TenantPayout;
use Livewire\WithPagination;

class PayoutsIndexPage extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $tenantFilter = '';
    public string $statusFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Tenant Payouts',
            'badge' => 'Finance',
            'actionLabel' => 'Record Payout',
            'actionUrl' => route('admin.wallets.index'),
            'description' => 'All payouts recorded from the central platform to tenants.',
            'tableTitle' => 'Payout Records',
            'headers' => ['Invoice', 'Tenant', 'Method', 'Amount', 'Status', 'Paid At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $query = TenantPayout::query()
            ->when($this->tenantFilter, fn($q) => $q->where('tenant_id', $this->tenantFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('paid_at', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('tenant_name', 'like', "%{$this->search}%")
                        ->orWhere('tenant_email', 'like', "%{$this->search}%")
                        ->orWhere('transaction_reference', 'like', "%{$this->search}%");
                });
            })
            ->latest('paid_at');

        $records = $query->paginate(20);

        $totalCount = TenantPayout::query()->where('status', 'paid')->count();
        $totalPaidOut = TenantPayout::query()->where('status', 'paid')->sum('amount');
        $thisMonth = TenantPayout::query()
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $tenants = Tenant::query()->orderBy('name')->get()->mapWithKeys(
            fn(Tenant $t) => [$t->getTenantKey() => $t->name ?? $t->getTenantKey()]
        )->toArray();

        $statuses = TenantPayout::query()->distinct()->pluck('status', 'status')->filter()->toArray();

        $rows = collect($records->items())->map(function (TenantPayout $p) {
            $statusClass = match ($p->status) {
                'paid' => 'badge-green',
                'pending' => 'badge-amber',
                'cancelled' => 'badge-red',
                default => 'badge-cyan',
            };

            return [
                '<div class="entity-title" style="font-family:monospace;font-size:12px;">' . e($p->invoice_number) . '</div>',
                '<div class="entity-title">' . e($p->tenant_name ?? $p->tenant_id) . '</div>'
                . ($p->tenant_email ? '<div class="entity-subtitle">' . e($p->tenant_email) . '</div>' : ''),
                '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $p->method ?? '—'))) . '</div>',
                '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
                '<span class="status-pill ' . $statusClass . '">' . e(ucwords($p->status)) . '</span>',
                '<div class="entity-title">' . e($p->paid_at?->format('M d, Y')) . '</div>'
                . '<div class="entity-subtitle">' . e($p->paid_at?->format('H:i')) . '</div>',
                '<a class="btn btn-secondary btn-sm" href="' . route('admin.finance.payouts.edit', $p->id) . '">'
                . ($p->status === 'pending' ? 'Edit' : 'View') . '</a>',
            ];
        })->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice, tenant, transaction…'],
                [
                    'label' => 'Tenant',
                    'model' => 'tenantFilter',
                    'type' => 'select',
                    'options' => ['' => 'All Tenants'] + $tenants,
                ],
                [
                    'label' => 'Status',
                    'model' => 'statusFilter',
                    'type' => 'select',
                    'options' => ['' => 'All Statuses'] + array_map(fn($s) => ucwords($s), $statuses),
                ],
                ['label' => 'From', 'model' => 'dateFrom', 'type' => 'date'],
                ['label' => 'To', 'model' => 'dateTo', 'type' => 'date'],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Payouts', 'value' => $totalCount, 'format' => 'number', 'caption' => 'All-time paid payout records.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Paid Out', 'value' => $totalPaidOut, 'format' => 'currency', 'caption' => 'Cumulative payouts to tenants.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $thisMonth, 'format' => 'currency', 'caption' => 'Payouts made this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'rows' => $rows,
            'tableDescription' => $records->total() . ' payout records matched the current filters.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedTenantFilter(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }
}
