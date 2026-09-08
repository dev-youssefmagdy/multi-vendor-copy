<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Models\Tenant\Currency as TenantCurrency;
use App\Models\Tenant\Order as TenantOrder;
use App\Support\OrderProfitCalculator;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Aggregates per-tenant financial ledger from order data.
 *
 * Gateway-based financial model, driven entirely by OrderProfitCalculator's
 * vendor_gateway_id classification (see App\Support\OrderProfitCalculator):
 *
 *   - Tenant-gateway order (orderPaidFromTenantGatewayForOrder = true, i.e.
 *     vendor_gateway_id !== null):
 *       Vendor collected the customer payment themselves.
 *       Vendor OWES central:  tenantOwnCentralForOrder()  (effective owner profit)
 *       → central_profit_total = Σ tenantOwnCentralForOrder()  for these orders
 *
 *   - Central-gateway order (vendor_gateway_id === null):
 *       Central collected the customer payment.
 *       Central OWES vendor:  centralOwnTenantForOrder()  (grand_total − effective owner profit)
 *       → vendor_profit_total = Σ centralOwnTenantForOrder() for these orders
 *
 * Balance per tenant:
 *   vendor_balance  = vendor_profit_total − central_profit_total
 *                     (positive → central owes vendor net; negative → vendor owes central net)
 *   central_balance = central_profit_total − vendor_profit_total  (mirror)
 *
 * Outstanding amounts:
 *   tenant_owes_central = central_profit_total − Σ VendorSettlement.total for status='paid' settlements
 *   central_owes_tenant = vendor_profit_total − Σ TenantPayout.amount for status='paid' payouts  (clamped ≥ 0)
 *
 * Informational totals (across all paid orders, regardless of gateway):
 *   owner_profit_total, vendor_cost_total, vendor_gateway_fee_total
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
        return $this->ledger()->first(fn(object $entry) => (string) $entry->tenant_id === $tenantId);
    }

    /**
     * Build ledger entries for only the given tenant IDs, initializing tenancy
     * once per tenant instead of looping over every tenant in the system.
     *
     * @param array<int, string> $tenantIds
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
            'discount_total' => (float) $ledger->sum('discount_total') + (float) $ledger->sum('items_discount_total'),
            'tax_total' => (float) $ledger->sum('tax_total') + (float) $ledger->sum('items_tax_total'),
            'shipping_total' => (float) $ledger->sum('shipping_total'),
            'owner_profit_total' => (float) $ledger->sum('owner_profit_total'),
            'vendor_cost_total' => (float) $ledger->sum('vendor_cost_total'),
            'vendor_gateway_fee_total' => (float) $ledger->sum('vendor_gateway_fee_total'),
            // Gateway-based balance fields
            'vendor_profit_total' => (float) $ledger->sum('vendor_profit_total'),
            'central_profit_total' => (float) $ledger->sum('central_profit_total'),
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
        $orders = TenantOrder::query()->get();
        $paid = $orders->filter(fn(TenantOrder $o) => $o->paid);
        $cancelledLike = [OrderStatus::Cancelled, OrderStatus::Rejected];

        // Gateway classification: vendor_gateway_id !== null → vendor collected payment
        $isVendorGateway = fn(TenantOrder $o): bool =>
            OrderProfitCalculator::orderPaidFromTenantGatewayForOrder($o);

        // Effective owner profit: once a vendor purchase gateway is attached (vendor_gateway_id
        // set), central's cut for the order is the vendor_cost owed back to central, not the
        // originally recorded owner_profit. Mirrors OrderRepository / TenantAdminAggregateService.
        $effectiveOwnerProfit = fn(TenantOrder $o): float =>
            OrderProfitCalculator::effectiveOwnerProfitForOrder($o);

        $vendorGatewayPaid = $paid->filter($isVendorGateway);
        $centralGatewayPaid = $paid->filter(fn(TenantOrder $o) => !$isVendorGateway($o));

        // Informational totals across all paid orders
        $grossSales = (float) $orders->sum(fn(TenantOrder $o) => (float) $o->grand_total);
        $collectedSales = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->grand_total);
        $subtotal = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->subtotal);
        $itemsDiscount = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->items_discount);
        $discount = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->discount_amount);
        $itemsTax = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->items_tax);
        $tax = (float) $paid->sum(fn(TenantOrder $o) => (float) $o->tax_amount);
        $shipping = (float) $paid->sum(fn(TenantOrder $o) => (float) ($o->resolved_shipping_charge ?? $o->shipping_charge));
        $ownerProfit = (float) $paid->sum($effectiveOwnerProfit);
        $vendorCost = (float) $paid->sum(fn(TenantOrder $o) => (float) ($o->vendor_cost ?? 0));
        $vendorGatewayFee = (float) $paid->sum(fn(TenantOrder $o) => (float) ($o->vendor_gateway_fee ?? 0));

        // --- Gateway-based balance model ---
        // Vendor profit: central collected → vendor earns (grand_total − effective owner profit)
        $vendorProfitTotal = (float) $centralGatewayPaid->sum(
            fn(TenantOrder $o) => round(OrderProfitCalculator::centralOwnTenantForOrder($o), 2)
        );
        // Central profit: vendor collected → vendor owes central the effective owner profit
        $centralProfitTotal = (float) $vendorGatewayPaid->sum(
            fn(TenantOrder $o) => round(OrderProfitCalculator::tenantOwnCentralForOrder($o), 2)
        );

        $vendorBalance = round($vendorProfitTotal - $centralProfitTotal, 2);
        $centralBalance = round($centralProfitTotal - $vendorProfitTotal, 2);

        // Outstanding: vendor-gateway orders not yet settled with central
        $unsettled = $vendorGatewayPaid->filter(
            fn(TenantOrder $o) => $o->vendor_settled_at === null
        );


        // Outstanding: central profit owed to central, net of paid vendor settlements
        $settlementsReceived = (float) $tenantSettlements->sum('total');
        $settlementsReceivedPaid = (float) $tenantSettlements->where('status', 'paid')->sum('total');
        $tenantOwesCentral = max(0.0, round($centralProfitTotal - $settlementsReceivedPaid, 2));

        // Outstanding: central-gateway vendor profit not yet paid out
        $payoutsSent = (float) $tenantPayouts->sum('amount');
        $payoutsSentPaid = (float) $tenantPayouts->where('status', 'paid')->sum('amount');
        $centralOwesTenant = max(0.0, round($vendorProfitTotal - $payoutsSentPaid, 2));

        $pendingBalance = (float) $orders
            ->where('paid', false)
            ->filter(fn(TenantOrder $o) => !in_array($o->status, $cancelledLike, true))
            ->sum(fn(TenantOrder $o) => max(0, (float) $o->grand_total - $effectiveOwnerProfit($o)));

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
            'subtotal_total' => round($subtotal, 2),
            'items_discount_total' => round($itemsDiscount, 2),
            'discount_total' => round($discount, 2),
            'items_tax_total' => round($itemsTax, 2),
            'tax_total' => round($tax, 2),
            'shipping_total' => round($shipping, 2),
            'owner_profit_total' => round($ownerProfit, 2),
            'vendor_cost_total' => round($vendorCost, 2),
            'vendor_gateway_fee_total' => round($vendorGatewayFee, 2),
            // Gateway-based balance fields
            'vendor_profit_total' => round($vendorProfitTotal, 2),
            'central_profit_total' => round($centralProfitTotal, 2),
            'vendor_balance' => $vendorBalance,
            'central_balance' => $centralBalance,
            // Direction balances
            'pending_balance' => round($pendingBalance, 2),
            'tenant_owes_central' => $tenantOwesCentral,
            'central_owes_tenant' => $centralOwesTenant,
            'net_payable_to_tenant' => round(max(0.0, $centralOwesTenant), 2),
            'settlements_received' => round($settlementsReceived, 2),
            'payouts_sent' => round($payoutsSent, 2),
            'unsettled_orders' => $unsettled->map(fn(TenantOrder $o) => [
                'id' => $o->id,
                'uuid' => $o->uuid,
                'grand_total' => (float) $o->grand_total,
                'vendor_cost' => (float) ($o->vendor_cost ?? 0),
                'shipping_cost' => (float) ($o->resolved_shipping_charge ?? $o->shipping_charge ?? 0),
                'vendor_gateway_fee' => (float) ($o->vendor_gateway_fee ?? 0),
                'created_at' => $o->created_at,
            ])->values()->all(),
            'recent_paid_orders' => $paid->sortByDesc('created_at')->take(5)->map(fn(TenantOrder $o) => [
                'uuid' => $o->uuid,
                'grand_total' => (float) $o->grand_total,
                'owner_profit' => $effectiveOwnerProfit($o),
                'vendor_cost' => (float) ($o->vendor_cost ?? 0),
                'gateway_source' => $isVendorGateway($o) ? 'vendor' : 'central',
                'vendor_profit' => round(OrderProfitCalculator::centralOwnTenantForOrder($o), 2),
                'central_profit' => round(OrderProfitCalculator::tenantOwnCentralForOrder($o), 2),
            ])->values()->all(),
            'updated_at' => $orders->max('updated_at') ?? $tenant->updated_at,
        ];
    }
}
