<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Tenant;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\TenantPayout;
use App\Services\Admin\TenantLedgerService;
use App\Support\OrderPayoutLedger;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * /admin/finance/payouts/{payout}/edit — edit or cancel a recorded tenant payout.
 *
 * Editable only while status = pending. Transitioning pending → paid triggers
 * the same payout_released side effect as the create page. Once paid, the
 * record is read-only except for a cancel action, which flips status to
 * cancelled (excluding it from future remaining-profit sums) and reverses the
 * payout_released flag on the orders captured in this payout's snapshot.
 */
class TenantPayoutEditPage extends AdminPage
{
    use InteractsWithAdminUi;

    public string $payoutId = '';
    public string $tenantId = '';
    public string $originalStatus = '';

    public string $invoiceNumber = '';
    public string $method = 'bank';
    public string $bankName = '';
    public string $bankAccount = '';
    public string $gatewayName = '';
    public string $transactionReference = '';
    public string $amount = '';
    public string $status = 'pending';
    public string $note = '';
    public string $paidAt = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Edit Payout',
            'badge' => 'Finance · Record-Only',
            'description' => 'Update a pending tenant payout, or cancel a paid payout to exclude it from future balances.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.finance.tenant-payout-edit-page';
    }

    public function mount(string $payout): void
    {
        $this->authorizePermission('billing.payouts.manage');

        $record = TenantPayout::query()->findOrFail($payout);

        $this->payoutId = (string) $record->id;
        $this->tenantId = $record->tenant_id;
        $this->originalStatus = $record->status;

        $this->invoiceNumber = $record->invoice_number;
        $this->method = $record->method;
        $this->bankName = (string) $record->bank_name;
        $this->bankAccount = (string) $record->bank_account;
        $this->gatewayName = (string) $record->gateway_name;
        $this->transactionReference = (string) $record->transaction_reference;
        $this->amount = number_format((float) $record->amount, 2, '.', '');
        $this->status = $record->status;
        $this->note = (string) $record->note;
        $this->paidAt = $record->paid_at?->format('Y-m-d\TH:i') ?? '';
    }

    protected function isEditable(): bool
    {
        return $this->originalStatus === 'pending';
    }

    protected function remainingCap(): float
    {
        $entry = app(TenantLedgerService::class)->find($this->tenantId);

        return OrderPayoutLedger::remainingTenantProfitForTenant(
            $this->tenantId,
            (float) ($entry->vendor_profit_total ?? 0)
        );
    }

    public function save(): void
    {
        $this->authorizePermission('billing.payouts.manage');

        if (!$this->isEditable()) {
            $this->toast('Only pending payouts can be edited.', 'error');
            return;
        }

        $remaining = $this->remainingCap();

        $rules = [
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

        $record = TenantPayout::query()->findOrFail($this->payoutId);

        if ($record->status !== 'pending') {
            $this->toast('Only pending payouts can be edited.', 'error');
            return;
        }

        $transitioningToPaid = $this->status === 'paid';

        $record->fill([
            'method' => $this->method,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'bank_name' => $this->method === 'bank' ? $this->bankName : null,
            'bank_account' => $this->method === 'bank' ? ($this->bankAccount ?: null) : null,
            'gateway_name' => $this->method === 'gateway' ? $this->gatewayName : null,
            'transaction_reference' => $this->transactionReference ?: null,
            'note' => $this->note ?: null,
            'paid_at' => Carbon::parse($this->paidAt),
        ])->save();

        if ($transitioningToPaid) {
            $this->releaseOrdersForTenant();

            app(\App\Services\TenantNotificationService::class)->notifyById(
                tenantId: $record->tenant_id,
                type: 'payment',
                title: 'Payout Released',
                message: sprintf('Your payout of %s has been released.', number_format((float) $record->amount, 2)),
                data: ['payout_id' => $record->id, 'amount' => $record->amount],
            );
            app(\App\Services\AdminNotificationService::class)->notify(
                type: 'payment',
                title: 'Payout Marked Paid',
                message: sprintf('Payout #%d for tenant %s (%s) marked as paid.', $record->id, $record->tenant_id, number_format((float) $record->amount, 2)),
                data: ['payout_id' => $record->id, 'tenant_id' => $record->tenant_id],
            );
        }

        $this->toast('Payout updated successfully.');
        $this->redirect(route('admin.finance.payouts.index'));
    }

    public function cancelPayout(): void
    {
        $this->authorizePermission('billing.payouts.manage');

        $record = TenantPayout::query()->findOrFail($this->payoutId);

        if ($record->status !== 'paid') {
            $this->toast('Only paid payouts can be cancelled.', 'error');
            return;
        }

        $this->reverseReleaseForOrders($record);

        $record->update(['status' => 'cancelled']);

        $this->toast('Payout cancelled and excluded from future balances.');
        $this->redirect(route('admin.finance.payouts.index'));
    }

    protected function releaseOrdersForTenant(): void
    {
        try {
            $tenant = Tenant::query()->find($this->tenantId);
            if (!$tenant) {
                return;
            }

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
        } catch (Throwable) {
            // Best-effort; payout already saved.
        }
    }

    protected function reverseReleaseForOrders(TenantPayout $record): void
    {
        $uuids = collect($record->orders_snapshot ?? [])->pluck('uuid')->filter()->values()->all();

        if ($uuids === []) {
            return;
        }

        try {
            $tenant = Tenant::query()->find($record->tenant_id);
            if (!$tenant) {
                return;
            }

            tenancy()->initialize($tenant);
            try {
                TenantOrder::query()
                    ->whereIn('uuid', $uuids)
                    ->where('payout_released', true)
                    ->update(['payout_released' => false]);
            } finally {
                tenancy()->end();
            }
        } catch (Throwable) {
            // Best-effort; cancellation still proceeds.
        }
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'editable' => $this->isEditable(),
            'originalStatus' => $this->originalStatus,
        ]);
    }
}
