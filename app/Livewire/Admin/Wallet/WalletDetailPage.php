<?php

namespace App\Livewire\Admin\Wallet;

use App\Livewire\Admin\Base\AdminPage;
use App\Models\Tenant;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Services\Admin\TenantLedgerService;
use Throwable;

class WalletDetailPage extends AdminPage
{
    public string $tenantId = '';
    public array $row = [];

    // Mark-as-paid modal state
    public bool $showMarkPaidModal = false;
    public int $markOrderId = 0;
    public string $markOrderUuid = '';
    public float $markAmount = 0.0;
    public string $markMethod = 'manual'; // manual | payout_offset
    public string $markReference = '';
    public string $markNote = '';
    public ?string $markError = null;
    public bool $markSuccess = false;

    public function mount(string $tenantId): void
    {
        $this->authorizePermission('billing.wallets.view');
        $this->tenantId = $tenantId;

        $entry = app(TenantLedgerService::class)->find($tenantId);
        abort_if(!$entry, 404);

        $this->row = (array) $entry;
    }

    public function openMarkPaid(int $orderId, string $uuid, float $amount): void
    {
        $this->authorizePermission('billing.wallets.view');
        $this->markOrderId = $orderId;
        $this->markOrderUuid = $uuid;
        $this->markAmount = $amount;
        $this->markMethod = 'manual';
        $this->markReference = '';
        $this->markNote = '';
        $this->markError = null;
        $this->markSuccess = false;
        $this->showMarkPaidModal = true;
    }

    public function updatedMarkMethod(): void
    {
        $this->markError = null;
    }

    public function confirmMarkPaid(): void
    {
        $this->authorizePermission('billing.wallets.view');
        $this->markError = null;
        $this->markSuccess = false;

        if ($this->markMethod === 'manual' && trim($this->markReference) === '') {
            $this->markError = 'A payment reference is required for manual settlements.';
            return;
        }

        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            $this->markError = 'Tenant not found.';
            return;
        }

        try {
            tenancy()->initialize($tenant);

            $order = TenantOrder::find($this->markOrderId);

            if (!$order) {
                tenancy()->end();
                $this->markError = 'Order not found in tenant database.';
                return;
            }

            if ($order->vendor_settled_at !== null) {
                tenancy()->end();
                $this->markError = 'This order is already marked as settled.';
                return;
            }

            $reference = $this->markMethod === 'payout_offset'
                ? 'PAYOUT-OFFSET-' . strtoupper(substr($this->tenantId, 0, 8)) . '-' . $order->id
                : trim($this->markReference);

            $order->forceFill([
                // 'vendor_gateway_id' => null,
                // 'vendor_gateway_fee' => 0,
                'vendor_settled_at' => now(),
                'vendor_settlement_ref' => $reference,
            ])->save();

            tenancy()->end();

            // Central DB: record the settlement
            VendorSettlement::create([
                'tenant_id' => $this->tenantId,
                'order_id' => $this->markOrderId,
                'order_uuid' => $this->markOrderUuid,
                'invoice_number' => VendorSettlement::nextInvoiceNumber($this->tenantId),
                'tenant_name' => $this->row['tenant_name'] ?? '',
                'tenant_email' => $this->row['tenant_email'] ?? '',
                'product_cost' => $this->markAmount,
                'shipping_cost' => 0,
                'gateway_fee' => 0,
                'total' => $this->markAmount,
                'gateway_code' => $this->markMethod === 'payout_offset' ? 'payout_offset' : 'manual',
                'transaction_id' => $reference,
                'status' => 'completed',
                'meta' => [
                    'method' => $this->markMethod,
                    'note' => trim($this->markNote),
                    'admin' => auth()->user()?->name ?? 'admin',
                ],
                'settled_at' => now(),
            ]);

            TenantPayout::create([
                'tenant_id' => $this->tenantId,
                'invoice_number' => TenantPayout::nextInvoiceNumber($this->tenantId),
                'tenant_name' => $this->row['tenant_name'] ?? '',
                'tenant_email' => $this->row['tenant_email'] ?? '',
                'amount' => $this->markAmount,
                'status' => 'paid',
                'currency' => 'USD',
                'method' => $this->markMethod === 'payout_offset' ? 'payout_offset' : 'manual',
                'transaction_reference' => $reference,
                'note' => trim($this->markNote),
                'admin_id' => auth()->id(),
                'paid_at' => now(),
                'orders_snapshot' => [
                    'order_id' => $this->markOrderId,
                    'order_uuid' => $this->markOrderUuid,
                    'amount' => $this->markAmount,
                ],
            ]);

            // Refresh ledger data
            $entry = app(TenantLedgerService::class)->find($this->tenantId);
            if ($entry) {
                $this->row = (array) $entry;
            }

            $this->markSuccess = true;
            $this->showMarkPaidModal = false;

        } catch (Throwable $e) {
            try {
                tenancy()->end();
            } catch (Throwable) {
            }
            $this->markError = 'Settlement failed: ' . $e->getMessage();
        }
    }

    public function closeMarkPaid(): void
    {
        $this->showMarkPaidModal = false;
        $this->markError = null;
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->row['store_name'] ?? 'Tenant Ledger',
            'badge' => 'Wallet Detail',
            'description' => 'Per-tenant ledger detail — order totals, settlements, payouts, and outstanding balances.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.wallet.wallet-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'row' => $this->row,
        ]);
    }
}
