<?php

namespace App\Livewire\Admin\PaymentLog;

use App\Enums\PaymentLogStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Repositories\PaymentLogRepository;
use Livewire\WithPagination;

class PaymentLogsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $gatewayFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Logs',
            'badge' => 'Tenant Orders',
            'description' => 'Inspect gateway payloads and payment outcomes captured on tenant marketplace orders.',
            'actionLabel' => 'Export Logs',
            'tableTitle' => 'Marketplace Payment Logs',
            'headers' => ['Tenant', 'Order', 'Gateway', 'Amount', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(PaymentLogRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Gateway, tenant, or reference'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed']],
                ['label' => 'Gateway', 'model' => 'gatewayFilter', 'type' => 'select', 'options' => $repository->gatewayOptions()],
            ],
            'filtersNote' => 'Review order payment payloads and failed billing attempts across tenant storefronts.',
            'statistics' => $this->presentMetricCards([
                ['label' => 'All Logs', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Order payment records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Confirmed order payments', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Failed', 'value' => $stats['failed'], 'format' => 'number', 'caption' => 'Cancelled or rejected orders', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Processed Value', 'value' => $stats['amount'], 'format' => 'currency', 'caption' => 'Tracked order payment volume', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn($log) => [
                '<div class="entity-title">' . e($log->store_name ?? 'Unknown tenant') . '</div><div class="entity-subtitle">' . e($log->tenant?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . e($log->order_number) . '</div><div class="entity-subtitle">' . e($log->customer_name ?: 'Guest') . '</div>',
                '<div class="entity-title">' . e($log->gateway) . '</div><div class="entity-subtitle">' . e($log->reference ?? 'No reference') . '</div>',
                '$' . number_format((float) $log->amount, 2),
                '<span class="badge ' . ($log->status === PaymentLogStatus::Paid ? 'badge-green' : ($log->status === PaymentLogStatus::Failed ? 'badge-red' : 'badge-amber')) . '">' . e($log->status->label()) . '</span>',
                '<a href="' . route('admin.payment-logs.show', [$log->tenant_id, $log->order_number]) . '" class="link-btn">View</a>',
            ])->all(),
            'tableDescription' => $records->total() . ' marketplace payment logs matched the current filters.',
            'emptyCopy' => 'No payment logs matched the current search and status filters.',
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

    public function updatedGatewayFilter(): void
    {
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'payment-logs-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Store', 'Store Email', 'Order #', 'Customer', 'Gateway', 'Reference', 'Amount', 'Status', 'Logged At'];
    }

    protected function exportRows(): array
    {
        $repository = app(PaymentLogRepository::class);
        return $repository->export([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'gateway' => $this->gatewayFilter,
        ])->map(fn($log) => [
                $log->store_name ?? 'Unknown',
                $log->tenant?->email ?? '',
                $log->order_number,
                $log->customer_name ?: 'Guest',
                $log->gateway,
                $log->reference ?? '',
                number_format((float) $log->amount, 2),
                $log->status->label(),
                $log->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'gatewayFilter']);
        $this->resetPage();
    }
}
