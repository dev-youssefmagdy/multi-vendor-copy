<?php

namespace App\Livewire\Admin\Wallet;

use App\Enums\InvoiceStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Repositories\InvoiceRepository;
use App\Services\Admin\MarketplaceInvoiceService;
use Livewire\WithPagination;

class InvoicesList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected bool $exportable = true;

    public function mount(MarketplaceInvoiceService $invoiceService): void
    {
        $invoiceService->syncMarketplaceInvoices(false);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Invoices',
            'badge' => 'Billing Output',
            'description' => 'Read paid-order invoices generated from tenant storefront payments, including PDF output and email delivery state.',
            'actionLabel' => 'Sync Paid Orders',
            'tableTitle' => 'Invoices Registry',
            'headers' => ['Invoice', 'Tenant', 'Customer', 'Amount', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(InvoiceRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice or tenant'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'paid' => 'Paid', 'draft' => 'Draft', 'due' => 'Due']],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Invoices', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Billing documents issued', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Completed invoices', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'PDF Ready', 'value' => $stats['pdf_ready'], 'format' => 'number', 'caption' => 'Invoice PDFs stored for download', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Billed Amount', 'value' => $stats['amount'], 'format' => 'currency', 'caption' => 'Total invoiced marketplace value', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn($invoice) => [
                '<div class="entity-title">' . e($invoice->invoice_number) . '</div><div class="entity-subtitle">' . e($invoice->order_number ?: 'No order') . '</div>',
                '<div class="entity-title">' . e($invoice->tenant?->name ?? 'Unknown tenant') . '</div><div class="entity-subtitle">' . e($invoice->tenant?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . e($invoice->customer_name ?: 'Guest') . '</div><div class="entity-subtitle">' . e($invoice->customer_email ?: 'No email') . '</div>',
                '$' . number_format((float) $invoice->amount, 2),
                '<span class="badge ' . ($invoice->status === InvoiceStatus::Paid ? 'badge-green' : 'badge-amber') . '">' . e($invoice->status->label()) . '</span>',
                '<div class="entity-title">' . e($invoice->issued_at?->format('M d, Y') ?? '-') . '</div><div class="entity-subtitle">' . e(data_get($invoice->meta, 'email_sent_at') ? 'Emailed' : 'Email pending') . '</div>',
                '<div class="entity-title"><a href="' . route('admin.invoices.show', $invoice->id) . '" class="link-btn">View</a></div>'
                . ($invoice->pdf_url ? '<div class="entity-subtitle"><a class="link-btn" href="' . e($invoice->pdf_url) . '" target="_blank" rel="noopener">PDF</a></div>' : ''),
            ])->all(),
            'tableDescription' => $records->total() . ' invoices matched the billing filters.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'invoices-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Invoice #', 'Order #', 'Tenant', 'Tenant Email', 'Customer', 'Customer Email', 'Amount', 'Status', 'Issued At', 'Email Sent'];
    }

    protected function exportRows(): array
    {
        $repository = app(InvoiceRepository::class);
        return $repository->export([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ])->map(fn($invoice) => [
                $invoice->invoice_number,
                $invoice->order_number ?? '',
                $invoice->tenant?->name ?? 'Unknown',
                $invoice->tenant?->email ?? '',
                $invoice->customer_name ?: 'Guest',
                $invoice->customer_email ?: '',
                number_format((float) $invoice->amount, 2),
                $invoice->status->label(),
                $invoice->issued_at?->format('Y-m-d') ?? '',
                data_get($invoice->meta, 'email_sent_at') ? 'Yes' : 'No',
            ])->all();
    }
}
