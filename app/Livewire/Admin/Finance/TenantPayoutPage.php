<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Mail\TenantPayoutMail;
use App\Models\Tenant;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\TenantPayout;
use App\Services\Admin\TenantLedgerService;
use App\Support\OrderPayoutLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * /admin/finance/payouts/create/{tenant} — Record a payout from central to tenant.
 *
 * Record-only: admin enters invoice number, bank/gateway name, transaction
 * reference and amount; we persist a TenantPayout row and email the tenant
 * admin a confirmation. The new row reduces central_owes_tenant on the ledger.
 */
class TenantPayoutPage extends AdminPage
{
    use InteractsWithAdminUi;

    public string $tenantId = '';
    public ?object $ledgerEntry = null;

    public string $invoiceNumber = '';
    public string $method = 'bank';
    public string $bankName = '';
    public string $bankAccount = '';
    public string $gatewayName = '';
    public string $transactionReference = '';
    public string $amount = '';
    public string $status = 'paid';
    public string $note = '';
    public string $paidAt = '';

    public bool $sendEmail = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Pay Out to Tenant',
            'badge' => 'Finance · Record-Only',
            'description' => 'Record a payout from the central platform to a tenant. Stores the invoice, bank/gateway details and notifies the tenant admin via email.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.finance.tenant-payout-page';
    }

    public function mount(string $tenant): void
    {
        $this->authorizePermission('billing.payouts.manage');

        $this->tenantId = $tenant;
        $this->ledgerEntry = app(TenantLedgerService::class)->find($tenant);

        if (!$this->ledgerEntry) {
            abort(404, 'Tenant not found');
        }

        $this->invoiceNumber = TenantPayout::nextInvoiceNumber($tenant);
        $remaining = OrderPayoutLedger::remainingTenantProfitForTenant($tenant, (float) $this->ledgerEntry->vendor_profit_total);
        $this->amount = number_format(max(0.0, $remaining), 2, '.', '');
        $this->paidAt = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $this->authorizePermission('billing.payouts.manage');

        $remaining = OrderPayoutLedger::remainingTenantProfitForTenant($this->tenantId, (float) ($this->ledgerEntry->vendor_profit_total ?? 0));

        $rules = [
            'invoiceNumber' => ['required', 'string', 'max:128', 'unique:tenant_payouts,invoice_number'],
            'method' => ['required', 'in:bank,gateway'],
            'status' => ['required', 'in:pending,paid'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . number_format(max(0.0, $remaining), 2, '.', '')],
            'transactionReference' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:2000'],
            'paidAt' => ['required', 'date'],
        ];

        if ($this->method === 'bank') {
            $rules['bankName'] = ['required', 'string', 'max:191'];
            $rules['bankAccount'] = ['nullable', 'string', 'max:191'];
        } else {
            $rules['gatewayName'] = ['required', 'string', 'max:191'];
        }

        $this->validate($rules);

        $payout = TenantPayout::query()->create([
            'tenant_id' => $this->tenantId,
            'invoice_number' => $this->invoiceNumber,
            'tenant_name' => $this->ledgerEntry->store_name ?? $this->ledgerEntry->tenant_name,
            'tenant_email' => $this->ledgerEntry->tenant_email,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'currency' => $this->ledgerEntry->currency ?? 'USD',
            'method' => $this->method,
            'bank_name' => $this->method === 'bank' ? $this->bankName : null,
            'bank_account' => $this->method === 'bank' ? ($this->bankAccount ?: null) : null,
            'gateway_name' => $this->method === 'gateway' ? $this->gatewayName : null,
            'transaction_reference' => $this->transactionReference ?: null,
            'note' => $this->note ?: null,
            'orders_snapshot' => collect($this->ledgerEntry->recent_paid_orders ?? [])->take(20)->all(),
            'admin_id' => auth('admin')->id(),
            'paid_at' => Carbon::parse($this->paidAt),
        ]);

        // Mark all unreleased central-gateway paid orders for this tenant as paid out.
        if ($this->status === 'paid') {
            try {
                $tenant = Tenant::query()->find($this->tenantId);
                if ($tenant) {
                    tenancy()->initialize($tenant);
                    try {
                        TenantOrder::query()
                            ->where('paid', true)
                            ->where('payout_released', false)
                            ->whereNull('vendor_gateway_id')
                            ->update(['payout_released' => true]);
                    } finally {
                        tenancy()->end();
                    }
                }
            } catch (Throwable) {
                // Best-effort; payout already saved.
            }
        }

        if ($this->status === 'paid' && $this->sendEmail) {
            try {
                $tenant = $tenant ?? Tenant::query()->find($this->tenantId);
                if ($tenant) {
                    $emails = [];
                    try {
                        tenancy()->initialize($tenant);
                        $emails = TenantAdminUser::query()->where('status', 'active')->pluck('email')->filter()->all();
                    } catch (Throwable) {
                        $emails = [];
                    } finally {
                        tenancy()->end();
                    }

                    foreach ($emails as $email) {
                        Mail::to($email)->send(new TenantPayoutMail($payout));
                    }
                }
            } catch (Throwable) {
                // Email best-effort; payout already saved.
            }
        }

        $this->toast('Payout recorded successfully.');
        $this->redirect(route('admin.finance.payouts.index'));
    }
}
