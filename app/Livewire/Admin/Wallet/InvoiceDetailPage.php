<?php

namespace App\Livewire\Admin\Wallet;

use App\Livewire\Admin\Base\AdminPage;
use App\Repositories\InvoiceRepository;

class InvoiceDetailPage extends AdminPage
{
    public int $invoiceId = 0;
    public array $invoice = [];

    public function mount(int $invoiceId): void
    {
        $this->authorizePermission('billing.invoices.view');
        $this->invoiceId = $invoiceId;

        $record = app(InvoiceRepository::class)->find($invoiceId);
        abort_if(!$record, 404);

        $this->invoice = [
            'invoice_number' => $record->invoice_number,
            'order_number' => $record->order_number,
            'tenant_name' => $record->tenant?->name,
            'tenant_email' => $record->tenant?->email,
            'customer_name' => $record->customer_name,
            'customer_email' => $record->customer_email,
            'currency' => $record->currency,
            'amount' => (float) $record->amount,
            'status' => $record->status->label(),
            'payment_method' => $record->payment_method,
            'payment_reference' => $record->payment_reference,
            'issued_at' => $record->issued_at,
            'paid_at' => $record->paid_at,
            'pdf_url' => $record->pdf_url,
            'meta' => $record->meta ?? [],
        ];
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->invoice['invoice_number'] ?? 'Invoice Details',
            'badge' => $this->invoice['tenant_name'] ?? 'Billing',
            'description' => 'Full invoice detail — financial snapshot, line items, shipping address, and payment payload.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.wallet.invoice-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'invoice' => $this->invoice,
        ]);
    }
}
