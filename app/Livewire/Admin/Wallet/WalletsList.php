<?php

namespace App\Livewire\Admin\Wallet;

use App\Livewire\Admin\Base\ListPage;
use App\Services\Admin\TenantLedgerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/**
 * /admin/wallets — Tenants Ledger.
 *
 * Reads its values from each tenant's orders (subtotal, discount, shipping, tax,
 * owner profit, vendor cost, vendor gateway fee) and combines them with central
 * vendor_settlements (tenant -> central) and tenant_payouts (central -> tenant)
 * to compute the final outstanding balances in both directions.
 */
class WalletsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $directionFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Tenants Ledger',
            'badge' => 'Order-Driven Wallets',
            'description' => 'Per-tenant financial ledger computed from orders. Tracks gross sales, central commission, vendor cost, gateway fees, settlements received and payouts sent.',
            'actionLabel' => 'View Reports',
            'actionUrl' => route('admin.finance.reports'),
            'tableTitle' => 'Tenant Balances',
            'headers' => ['Tenant', 'Gross / Collected', 'Vendor Profit', 'Central Profit', 'Tenant → Central', 'Central → Tenant', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        /** @var TenantLedgerService $service */
        $service = app(TenantLedgerService::class);
        $entries = $service->ledger();
        $totals = $service->totals();

        $paginator = $this->paginate($this->filter($entries));

        return array_merge(parent::pageData(), [
            'records' => $paginator,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Tenant name or email'],
                [
                    'label' => 'Direction',
                    'model' => 'directionFilter',
                    'type' => 'select',
                    'options' => [
                        '' => 'All tenants',
                        'tenant_owes_central' => 'Tenants who owe central',
                        'central_owes_tenant' => 'Tenants central owes',
                        'settled' => 'Fully settled',
                    ]
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Gross Sales', 'value' => $totals['gross_sales'], 'format' => 'currency', 'caption' => 'Σ grand_total of every order across tenants', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Vendor Profit Total', 'value' => $totals['vendor_profit_total'], 'format' => 'currency', 'caption' => 'Σ (grand_total − owner_profit) on central-gateway paid orders — central owes this to vendors', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Tenants Owe Central', 'value' => $totals['tenants_owe_central'], 'format' => 'currency', 'caption' => 'Unsettled vendor_cost on vendor-gateway paid orders', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Central Owes Tenants', 'value' => $totals['central_owes_tenants'], 'format' => 'currency', 'caption' => 'Vendor profit on central-gateway orders not yet paid out', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($paginator->items())->map(fn(object $r) => [
                '<div class="entity-title">' . e($r->store_name) . '</div><div class="entity-subtitle">' . e($r->tenant_email ?? $r->tenant_id) . '</div>',
                '<div class="entity-title">$' . number_format($r->gross_sales, 2) . '</div><div class="entity-subtitle">$' . number_format($r->collected_sales, 2) . ' collected · ' . $r->paid_orders_count . '/' . $r->orders_count . ' paid</div>',
                '<div class="entity-title">$' . number_format($r->vendor_profit_total, 2) . '</div><div class="entity-subtitle">central-gw: grand_total − owner_profit</div>',
                '<div class="entity-title">$' . number_format($r->central_profit_total, 2) . '</div><div class="entity-subtitle">vendor-gw: vendor_cost · $' . number_format($r->settlements_received, 2) . ' settled</div>',
                $r->tenant_owes_central > 0
                ? '<strong style="color:#f59e0b;">$' . number_format($r->tenant_owes_central, 2) . '</strong><div class="entity-subtitle">' . count($r->unsettled_orders) . ' unsettled orders</div>'
                : '<span class="entity-subtitle">$0.00</span>',
                $r->central_owes_tenant > 0
                ? '<strong style="color:#f59e0b;">$' . number_format($r->central_owes_tenant, 2) . '</strong><div class="entity-subtitle">$' . number_format($r->payouts_sent, 2) . ' paid out</div>'
                : '<span class="entity-subtitle">$0.00</span>',
                $this->actionsCell($r),
            ])->all(),
            'tableDescription' => $paginator->total() . ' tenants matched the current filters.',
        ]);
    }

    protected function actionsCell(object $r): string
    {
        $payoutBtn = $r->net_payable_to_tenant > 0
            ? '<a class="btn btn-primary btn-sm"  href="' . route('admin.finance.payouts.create', $r->tenant_id) . '">Pay Out</a>'
            : '';
        $viewBtn = '<a href="' . route('admin.wallets.show', (string) $r->tenant_id) . '" class="btn btn-secondary btn-sm">View</a>';
        return '<div class="flex gap-2 flex-wrap">' . $viewBtn . $payoutBtn . '</div>';
    }

    protected function percent(float $part, float $whole): string
    {
        if ($whole <= 0) {
            return '—';
        }
        return number_format($part / $whole * 100, 1) . '%';
    }

    protected function filter(Collection $entries): Collection
    {
        $search = trim(strtolower($this->search));
        $direction = $this->directionFilter;

        return $entries
            ->filter(function (object $r) use ($search, $direction) {
                if ($search !== '') {
                    $hay = strtolower(implode(' ', array_filter([$r->tenant_name, $r->tenant_email, $r->store_name])));
                    if (!str_contains($hay, $search)) {
                        return false;
                    }
                }

                return match ($direction) {
                    'tenant_owes_central' => $r->tenant_owes_central > 0,
                    'central_owes_tenant' => $r->central_owes_tenant > 0,
                    'settled' => $r->tenant_owes_central <= 0 && $r->central_owes_tenant <= 0,
                    default => true,
                };
            })
            ->sortByDesc(fn(object $r) => $r->central_owes_tenant + $r->tenant_owes_central)
            ->values();
    }

    protected function paginate(Collection $items, int $perPage = 10): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage() ?: 1;
        return new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'directionFilter']);
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'tenant-ledger-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Store', 'Tenant Email', 'Gross Sales', 'Collected Sales', 'Orders', 'Paid Orders', 'Vendor Profit', 'Central Profit', 'Tenant Owes Central', 'Central Owes Tenant', 'Net Payable To Tenant'];
    }

    protected function exportRows(): array
    {
        /** @var TenantLedgerService $service */
        $service = app(TenantLedgerService::class);
        return $this->filter($service->ledger())->map(fn(object $r) => [
            $r->store_name ?? '',
            $r->tenant_email ?? '',
            number_format((float) $r->gross_sales, 2),
            number_format((float) $r->collected_sales, 2),
            $r->orders_count,
            $r->paid_orders_count,
            number_format((float) $r->vendor_profit_total, 2),
            number_format((float) $r->central_profit_total, 2),
            number_format((float) $r->tenant_owes_central, 2),
            number_format((float) $r->central_owes_tenant, 2),
            number_format((float) $r->net_payable_to_tenant, 2),
        ])->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDirectionFilter(): void
    {
        $this->resetPage();
    }
}
