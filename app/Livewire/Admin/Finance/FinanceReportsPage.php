<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Base\AdminPage;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Services\Admin\TenantLedgerService;

/**
 * /admin/finance/reports — Aggregate finance reports.
 *
 * Tabs:
 *  - overview          : platform-wide totals (gross, owner profit, settlements, payouts)
 *  - tenants_owe       : ranked list of tenants owing central
 *  - central_owes      : ranked list of tenants the central platform owes
 *  - settlements       : recent vendor_settlements
 *  - payouts           : recent tenant_payouts
 */
class FinanceReportsPage extends AdminPage
{
    public string $tab = 'overview';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Finance Reports',
            'badge' => 'Reports',
            'description' => 'Cross-tenant financial reports computed from order data, vendor settlements and tenant payouts.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.finance.finance-reports-page';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['overview', 'tenants_owe', 'central_owes', 'settlements', 'payouts'], true)
            ? $tab : 'overview';
    }

    protected function pageData(): array
    {
        /** @var TenantLedgerService $service */
        $service = app(TenantLedgerService::class);
        $ledger = $service->ledger();
        $totals = $service->totals();

        return array_merge(parent::pageData(), [
            'tab' => $this->tab,
            'totals' => $totals,
            'ledger' => $ledger,
            'tenantsOwe' => $ledger->where('tenant_owes_central', '>', 0)->sortByDesc('tenant_owes_central')->values(),
            'centralOwes' => $ledger->where('central_owes_tenant', '>', 0)->sortByDesc('central_owes_tenant')->values(),
            'recentSettlements' => VendorSettlement::query()->latest('settled_at')->limit(50)->get(),
            'recentPayouts' => TenantPayout::query()->latest('paid_at')->limit(50)->get(),
        ]);
    }
}
