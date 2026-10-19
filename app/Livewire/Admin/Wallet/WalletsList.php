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
 * platform profit, vendor profit, vendor gateway fee) and combines them with central
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
            'description' => 'Per-tenant financial ledger computed from orders. Tracks gross sales, platform profit, vendor profit, gateway fees, settlements received and payouts sent.',
            'actionLabel' => 'View Reports',
            'actionUrl' => route('admin.finance.reports'),
            'tableTitle' => 'Tenant Balances',
            'headers' => ['Tenant', 'Gross / Collected', 'Vendor Profit', 'Platform Profit', 'Vendor Owes Platform', 'Platform Owes Vendor', 'Actions'],
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
                        'vendor_own_platform' => 'Vendors who owe the platform',
                        'platform_own_vendor' => 'Vendors the platform owes',
                        'settled' => 'Fully settled',
                    ]
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Gross Sales', 'value' => $totals['gross_sales'], 'format' => 'currency', 'caption' => 'Σ grand_total of every order across tenants', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Vendor Profit Total', 'value' => $totals['vendor_profit_total'], 'format' => 'currency', 'caption' => 'Σ vendor_profit on paid orders', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Tenants Owe Central', 'value' => $totals['vendor_own_platform_total'], 'format' => 'currency', 'caption' => 'Σ vendor_own_platform: platform profit not yet settled on vendor-collected orders', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Central Owes Tenants', 'value' => $totals['platform_own_vendor_total'], 'format' => 'currency', 'caption' => 'Σ platform_own_vendor: vendor profit not yet paid out on platform-collected orders', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($paginator->items())->map(fn(object $r) => [
                '<div class="entity-title">' . e($r->store_name) . '</div><div class="entity-subtitle">' . e($r->tenant_email ?? $r->tenant_id) . '</div>',
                '<div class="entity-title">$' . number_format($r->gross_sales, 2) . '</div><div class="entity-subtitle">$' . number_format($r->collected_sales, 2) . ' collected · ' . $r->paid_orders_count . '/' . $r->orders_count . ' paid</div>',
                '<div class="entity-title">$' . number_format($r->vendor_profit_total, 2) . '</div><div class="entity-subtitle">Σ vendor_profit</div>',
                '<div class="entity-title">$' . number_format($r->platform_profit_total, 2) . '</div><div class="entity-subtitle">Σ platform_profit · $' . number_format($r->settlements_received, 2) . ' settled</div>',
                $r->vendor_own_platform_total > 0
                ? '<strong style="color:#f59e0b;">$' . number_format($r->vendor_own_platform_total, 2) . '</strong><div class="entity-subtitle">' . count($r->unsettled_orders) . ' unsettled orders</div>'
                : '<span class="entity-subtitle">$0.00</span>',
                $r->platform_own_vendor_total > 0
                ? '<strong style="color:#f59e0b;">$' . number_format($r->platform_own_vendor_total, 2) . '</strong><div class="entity-subtitle">$' . number_format($r->payouts_sent, 2) . ' paid out</div>'
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
                    'vendor_own_platform' => $r->vendor_own_platform_total > 0,
                    'platform_own_vendor' => $r->platform_own_vendor_total > 0,
                    'settled' => $r->vendor_own_platform_total <= 0 && $r->platform_own_vendor_total <= 0,
                    default => true,
                };
            })
            ->sortByDesc(fn(object $r) => $r->platform_own_vendor_total + $r->vendor_own_platform_total)
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
            number_format((float) $r->platform_profit_total, 2),
            number_format((float) $r->vendor_own_platform_total, 2),
            number_format((float) $r->platform_own_vendor_total, 2),
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
