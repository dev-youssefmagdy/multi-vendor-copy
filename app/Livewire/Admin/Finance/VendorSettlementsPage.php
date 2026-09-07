<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Base\ListPage;
use App\Models\Tenant;
use App\Models\VendorSettlement;
use App\Services\Admin\TenantLedgerService;
use Livewire\WithPagination;

class VendorSettlementsPage extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $gatewayFilter = '';
    public string $tenantFilter = '';
    public string $statusFilter = 'paid';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Vendor Settlements',
            'badge' => 'Finance',
            'description' => 'All settlement payments received from vendors for product cost and shipping.',
            'tableTitle' => 'Settlement Records',
            'headers' => ['Invoice', 'Vendor', 'Order', 'Gateway', 'Transaction', 'Amount', 'Remaining Owner Profit', 'Settled At'],
        ];
    }

    protected function pageData(): array
    {
        $query = VendorSettlement::query()
            ->with('tenant')
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->tenantFilter, fn($q) => $q->where('tenant_id', $this->tenantFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('order_uuid', 'like', "%{$this->search}%")
                        ->orWhere('tenant_name', 'like', "%{$this->search}%")
                        ->orWhere('tenant_email', 'like', "%{$this->search}%")
                        ->orWhere('transaction_id', 'like', "%{$this->search}%");
                });
            })
            ->when($this->gatewayFilter, fn($q) => $q->where('gateway_code', $this->gatewayFilter))
            ->latest('settled_at');

        $records = $query->paginate(20);

        $totalCount = VendorSettlement::query()->where('status', 'paid')->count();
        $totalRevenue = VendorSettlement::query()->where('status', 'paid')->sum('total');
        $thisMonth = VendorSettlement::query()
            ->where('status', 'paid')
            ->whereMonth('settled_at', now()->month)
            ->whereYear('settled_at', now()->year)
            ->sum('total');

        $gateways = VendorSettlement::query()
            ->where('status', 'paid')
            ->distinct()
            ->pluck('gateway_code', 'gateway_code')
            ->filter()
            ->toArray();

        $statuses = VendorSettlement::query()
            ->distinct()
            ->pluck('status', 'status')
            ->filter()
            ->toArray();

        $tenants = Tenant::query()->orderBy('data->name')->get()->mapWithKeys(
            fn(Tenant $t) => [$t->getTenantKey() => $t->name ?? $t->getTenantKey()]
        )->toArray();

        // Batch remaining-owner-profit lookups: initialize tenancy once per
        // distinct tenant on this page, rather than per row.
        $tenantIds = collect($records->items())->pluck('tenant_id')->filter()->unique()->values()->all();
        $ledgerEntries = app(TenantLedgerService::class)->forTenants($tenantIds);

        $rows = collect($records->items())->map(function (VendorSettlement $s) use ($ledgerEntries) {
            $remaining = $ledgerEntries->get($s->tenant_id)?->tenant_owes_central;

            return [
                '<div class="entity-title" style="font-family:monospace;font-size:12px;">' . e($s->invoice_number) . '</div>',
                '<div class="entity-title">' . e($s->tenant_name ?? $s->tenant_id) . '</div>'
                . ($s->tenant_email ? '<div class="entity-subtitle">' . e($s->tenant_email) . '</div>' : ''),
                '<div class="entity-title" style="font-family:monospace;font-size:11px;">' . e(str()->limit($s->order_uuid, 28)) . '</div>',
                '<div class="entity-title">' . e(ucwords(str_replace('_', ' ', $s->gateway_code ?? '—'))) . '</div>',
                $s->transaction_id
                ? '<div class="entity-subtitle" style="font-family:monospace;font-size:11px;">' . e(str()->limit($s->transaction_id, 28)) . '</div>'
                : '<span class="entity-subtitle">—</span>',
                '<strong>$' . number_format((float) $s->total, 2) . '</strong>'
                . '<div class="entity-subtitle">Fee: $' . number_format((float) $s->gateway_fee, 2) . '</div>',
                $remaining === null
                ? '<span class="entity-subtitle">—</span>'
                : '$' . number_format((float) $remaining, 2),
                '<div class="entity-title">' . e($s->settled_at?->format('M d, Y')) . '</div>'
                . '<div class="entity-subtitle">' . e($s->settled_at?->format('H:i')) . '</div>',
            ];
        })->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice, order UUID, tenant, transaction…'],
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
                    'options' => ['' => 'All Statuses'] + array_map(fn($s) => ucwords(str_replace('_', ' ', $s)), $statuses),
                ],
                [
                    'label' => 'Gateway',
                    'model' => 'gatewayFilter',
                    'type' => 'select',
                    'options' => ['' => 'All Gateways'] + array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), $gateways)
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Settlements', 'value' => $totalCount, 'format' => 'number', 'caption' => 'All-time vendor settlement records.', 'dot' => 'dot-cyan'],
                ['label' => 'Total Received', 'value' => $totalRevenue, 'format' => 'currency', 'caption' => 'Cumulative settlement revenue.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'This Month', 'value' => $thisMonth, 'format' => 'currency', 'caption' => 'Settlements received this calendar month.', 'dot' => 'dot-cyan'],
            ]),
            'rows' => $rows,
            'tableDescription' => $records->total() . ' settlement records matched the current filters.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedGatewayFilter(): void
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
}
