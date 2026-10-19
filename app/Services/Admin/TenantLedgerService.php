<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use App\Models\Tenant\Currency as TenantCurrency;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Support\OrderProfitCalculator;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Aggregates per-tenant financial ledger from order data (docs/ORDER_FORMULAS.md sections 2-3).
 *
 * Every figure comes from OrderProfitCalculator over the tenant's paid, non-void orders:
 *   platform_profit_total      = Σ platform_profit
 *   vendor_profit_total        = Σ vendor_profit (net of partial refunds)
 *   vendor_own_platform_total  = Σ vendor_own_platform  (vendor-collected orders: tenants owe central)
 *   platform_own_vendor_total  = Σ platform_own_vendor  (platform-collected orders: central owes tenants)
 *
 * Settlements / payouts already paid are recorded per order (order_transactions) and are therefore
 * already subtracted from the *_own_* totals. Legacy aliases tenant_owes_central / central_owes_tenant
 * / net_payable_to_tenant mirror vendor_own_platform_total / platform_own_vendor_total for T3 pages.
 */
class TenantLedgerService
{
    /** @return Collection<int, object> */
    public function ledger(): Collection
    {
        $payouts = TenantPayout::query()->get()->groupBy('tenant_id');
        $settlements = VendorSettlement::query()->get()->groupBy('tenant_id');

        $entries = collect();
        $tenants = Tenant::query()->orderBy('id')->get();

        foreach ($tenants as $tenantRecord) {
            /** @var Tenant $tenant */
            $tenant = $tenantRecord;
            $initialized = false;

            try {
                tenancy()->initialize($tenant);
                $initialized = true;

                $entries->push($this->buildEntry(
                    $tenant,
                    $payouts->get($tenant->getTenantKey(), collect()),
                    $settlements->get($tenant->getTenantKey(), collect()),
                ));
            } catch (Throwable) {
                continue;
            } finally {
                if ($initialized) {
                    tenancy()->end();
                }
            }
        }

        return $entries->values();
    }

    public function find(string $tenantId): ?object
    {
        return $this->ledger()->first(fn (object $entry) => (string) $entry->tenant_id === $tenantId);
    }

    /**
     * Build ledger entries for only the given tenant IDs, initializing tenancy
     * once per tenant instead of looping over every tenant in the system.
     *
     * @param  array<int, string>  $tenantIds
     * @return Collection<string, object> keyed by tenant_id
     */
    public function forTenants(array $tenantIds): Collection
    {
        $tenantIds = array_values(array_unique(array_filter($tenantIds)));

        if ($tenantIds === []) {
            return collect();
        }

        $payouts = TenantPayout::query()->whereIn('tenant_id', $tenantIds)->get()->groupBy('tenant_id');
        $settlements = VendorSettlement::query()->whereIn('tenant_id', $tenantIds)->get()->groupBy('tenant_id');

        $entries = collect();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get();

        foreach ($tenants as $tenantRecord) {
            /** @var Tenant $tenant */
            $tenant = $tenantRecord;
            $initialized = false;

            try {
                tenancy()->initialize($tenant);
                $initialized = true;

                $entries->push($this->buildEntry(
                    $tenant,
                    $payouts->get($tenant->getTenantKey(), collect()),
                    $settlements->get($tenant->getTenantKey(), collect()),
                ));
            } catch (Throwable) {
                continue;
            } finally {
                if ($initialized) {
                    tenancy()->end();
                }
            }
        }

        return $entries->keyBy('tenant_id');
    }

    public function totals(): array
    {
        $ledger = $this->ledger();

        return [
            'gross_sales' => (float) $ledger->sum('gross_sales'),
            'collected_sales' => (float) $ledger->sum('collected_sales'),
            'refunded_total' => (float) $ledger->sum('refunded_total'),
            'discount_total' => (float) $ledger->sum('discount_total') + (float) $ledger->sum('items_discount_total'),
            'tax_total' => (float) $ledger->sum('tax_total') + (float) $ledger->sum('items_tax_total'),
            'shipping_total' => (float) $ledger->sum('shipping_total'),
            'platform_profit_total' => (float) $ledger->sum('platform_profit_total'),
            'vendor_profit_total' => (float) $ledger->sum('vendor_profit_total'),
            'vendor_gateway_fee_total' => (float) $ledger->sum('vendor_gateway_fee_total'),
            'vendor_own_platform_total' => (float) $ledger->sum('vendor_own_platform_total'),
            'platform_own_vendor_total' => (float) $ledger->sum('platform_own_vendor_total'),
            'platform_share_due_from_vendor_total' => (float) $ledger->sum('platform_share_due_from_vendor_total'),
            'vendor_share_due_from_platform_total' => (float) $ledger->sum('vendor_share_due_from_platform_total'),
            'vendor_balance' => (float) $ledger->sum('vendor_balance'),
            'central_balance' => (float) $ledger->sum('central_balance'),
            'tenants_owe_central' => (float) $ledger->sum('tenant_owes_central'),
            'central_owes_tenants' => (float) $ledger->sum('central_owes_tenant'),
            'settlements_received' => (float) $ledger->sum('settlements_received'),
            'payouts_sent' => (float) $ledger->sum('payouts_sent'),
        ];
    }

    protected function buildEntry(Tenant $tenant, Collection $tenantPayouts, Collection $tenantSettlements): object
    {
        $orders = TenantOrder::query()->with(['items', 'transactions'])->get();
        // Paid orders that still produce money: cancelled / rejected / refunded orders are excluded
        // from every payout, profit and revenue total (RETURN_EXCHANGE_REFUND_PLAN.md B.4).
        $allPaid = $orders->filter(fn (TenantOrder $o) => $o->paid);
        $paid = $allPaid->reject(fn (TenantOrder $o) => OrderProfitCalculator::isFinanciallyVoid($o));

        // Informational totals across all paid orders
        // Net of completed refunds; void orders count for 0.
        $grossSales = (float) $orders->sum(fn (TenantOrder $o) => OrderProfitCalculator::netOrderTotal($o));
        $collectedSales = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::netOrderTotal($o));
        $refundedTotal = (float) $allPaid->sum(fn (TenantOrder $o) => (float) ($o->refunded_amount ?? 0));
        $subtotal = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->subtotal);
        $fixedShipping = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->items_fixed_shipping);
        $itemsDiscount = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->items_discount);
        $discount = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->discount_amount);
        $itemsTax = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->items_tax);
        $tax = (float) $paid->sum(fn (TenantOrder $o) => (float) $o->tax_amount);
        $shipping = (float) $paid->sum(fn (TenantOrder $o) => (float) ($o->resolved_shipping_charge ?? $o->shipping_charge));
        $vendorGatewayFee = (float) $paid->sum(fn (TenantOrder $o) => (float) ($o->vendor_gateway_fee ?? 0));

        // --- Platform / vendor split (same numbers as the vendor panel) ---
        $platformProfitTotal = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::platformProfitForOrder($o));
        $vendorProfitTotal = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::vendorProfitForOrder($o));
        $platformShareDueFromVendor = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::platformShareDueFromVendor($o));
        $vendorShareDueFromPlatform = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::vendorShareDueFromPlatform($o));

        // Outstanding balances (per-order settlements / payouts already subtracted)
        $vendorOwnPlatformTotal = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::vendorOwnPlatformForOrder($o));
        $platformOwnVendorTotal = (float) $paid->sum(fn (TenantOrder $o) => OrderProfitCalculator::platformOwnVendorForOrder($o));

        $netBalance = round($platformOwnVendorTotal - $vendorOwnPlatformTotal, 2);

        $unsettled = $paid->filter(fn (TenantOrder $o) => OrderProfitCalculator::vendorOwnPlatformForOrder($o) > 0);

        $settlementsReceived = (float) $tenantSettlements->sum('total');
        $payoutsSent = (float) $tenantPayouts->sum('amount');

        $pendingBalance = (float) $orders
            ->where('paid', false)
            ->reject(fn (TenantOrder $o) => OrderProfitCalculator::isFinanciallyVoid($o))
            ->sum(fn (TenantOrder $o) => max(0, OrderProfitCalculator::vendorProfitForOrder($o)));

        $currency = TenantCurrency::query()->where('is_default', true)->value('code')
            ?? TenantCurrency::query()->orderByDesc('is_active')->value('code')
            ?? 'USD';

        return (object) [
            'tenant' => $tenant,
            'tenant_id' => $tenant->getTenantKey(),
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'store_name' => $tenant->data['shop_name'] ?? $tenant->name ?? 'Unknown store',
            'currency' => $currency,
            'orders_count' => $orders->count(),
            'paid_orders_count' => $paid->count(),
            'gross_sales' => round($grossSales, 2),
            'collected_sales' => round($collectedSales, 2),
            'refunded_total' => round($refundedTotal, 2),
            'subtotal_total' => round($subtotal, 2),
            'fixed_shipping_total' => round($fixedShipping, 2),
            'items_discount_total' => round($itemsDiscount, 2),
            'discount_total' => round($discount, 2),
            'items_tax_total' => round($itemsTax, 2),
            'tax_total' => round($tax, 2),
            'shipping_total' => round($shipping, 2),
            'vendor_gateway_fee_total' => round($vendorGatewayFee, 2),
            'platform_profit_total' => round($platformProfitTotal, 2),
            'vendor_profit_total' => round($vendorProfitTotal, 2),
            'platform_share_due_from_vendor_total' => round($platformShareDueFromVendor, 2),
            'vendor_share_due_from_platform_total' => round($vendorShareDueFromPlatform, 2),
            'vendor_own_platform_total' => round($vendorOwnPlatformTotal, 2),
            'platform_own_vendor_total' => round($platformOwnVendorTotal, 2),
            // Net: positive -> central owes the vendor; negative -> the vendor owes central.
            'vendor_balance' => $netBalance,
            'central_balance' => round(-$netBalance, 2),
            'pending_balance' => round($pendingBalance, 2),
            // Legacy aliases (T3 pages / older callers)
            'tenant_owes_central' => round($vendorOwnPlatformTotal, 2),
            'central_owes_tenant' => round($platformOwnVendorTotal, 2),
            'net_payable_to_tenant' => round(max(0.0, $platformOwnVendorTotal), 2),
            'settlements_received' => round($settlementsReceived, 2),
            'payouts_sent' => round($payoutsSent, 2),
            'unsettled_orders' => $unsettled->map(fn (TenantOrder $o) => [
                'id' => $o->id,
                'uuid' => $o->uuid,
                'grand_total' => (float) $o->grand_total,
                'platform_profit' => OrderProfitCalculator::platformProfitForOrder($o),
                'vendor_settlements_paid' => (float) $o->vendor_settlements_paid,
                'vendor_own_platform' => OrderProfitCalculator::vendorOwnPlatformForOrder($o),
                'shipping_cost' => (float) ($o->resolved_shipping_charge ?? $o->shipping_charge ?? 0),
                'vendor_gateway_fee' => (float) ($o->vendor_gateway_fee ?? 0),
                'created_at' => $o->created_at,
            ])->values()->all(),
            'recent_paid_orders' => $paid->sortByDesc('created_at')->take(5)->map(fn (TenantOrder $o) => [
                'uuid' => $o->uuid,
                'grand_total' => (float) $o->grand_total,
                'collected_by' => $o->collected_by,
                'platform_profit' => OrderProfitCalculator::platformProfitForOrder($o),
                'vendor_profit' => OrderProfitCalculator::vendorProfitForOrder($o),
                'vendor_own_platform' => OrderProfitCalculator::vendorOwnPlatformForOrder($o),
                'platform_own_vendor' => OrderProfitCalculator::platformOwnVendorForOrder($o),
            ])->values()->all(),
            'updated_at' => $orders->max('updated_at') ?? $tenant->updated_at,
        ];
    }
}
