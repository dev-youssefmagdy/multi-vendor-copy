<?php

namespace App\Livewire\Tenant\Order;

use App\Enums\OrderStatus;
use App\Livewire\Tenant\Base\ListPage;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\OrderProfitCalculator;
use Livewire\WithPagination;

class OrdersList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Orders',
            'badge' => 'Sales',
            'description' => 'Track tenant orders, payment collection, fulfillment progress, and full order payload details from one queue.',
'tableTitle' => 'Order Queue',
            'headers' => ['Order', 'Customer', 'Value', 'Commission', 'Status', 'Gateway', 'Placed At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateOrders(['search' => $this->search, 'status' => $this->statusFilter]);
        $stats = $repository->orderStats();

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order UUID or customer'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All'] + collect(OrderStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Orders in this tenant database.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Orders with successful payment capture.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Processing', 'value' => $stats['processing'], 'format' => 'number', 'caption' => 'Orders currently moving through fulfillment.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value collected by the tenant.', 'dot' => 'dot-violet'],
            ]),
            'rows' => collect($records->items())->map(fn(Order $order) => [
                '<div class="entity-title">' . e($order->uuid) . '</div><div class="entity-subtitle">' . e($order->items_count) . ' items · ' . e($order->items->sum('qty')) . ' units</div>',
                '<div class="entity-title">' . e($order->customer?->full_name ?? 'Guest') . '</div><div class="entity-subtitle">' . e($order->customer?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . number_format($order->grand_total, 2) . '</div>'
                . '<div class="entity-subtitle">'
                . ((float) $order->discount_percentage > 0 ? e(number_format((float) $order->discount_percentage, 1)) . '% off · ' : '')
                . 'ship ' . e(number_format($order->resolved_shipping_charge, 2))
                . '</div>',
                '<div class="entity-title">' . number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2) . '</div>'
                . '<div class="entity-subtitle">platform ' . number_format(OrderProfitCalculator::effectiveOwnerProfitForOrder($order), 2) . '</div>',
                '<span class="badge badge-cyan">' . e($order->status->label()) . '</span>',
                '<div class="entity-title">' . e(/*$order->paymentGateway?->name ?? */ str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString()) . '</div><div class="entity-subtitle">' . e($order->paid ? 'Paid' : 'Unpaid') . '</div>',
                e($order->created_at?->format('M d, Y H:i')),
                '<a href="' . route('tenant.orders.show', $order->id) . '" class="btn btn-secondary btn-sm">View</a>',
            ])->all(),
            'tableDescription' => $records->total() . ' orders matched the current queue filters.',
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
        return 'orders-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Grand Total', 'Discount %', 'Shipping', 'Vendor Commission', 'Status', 'Payment Method', 'Paid', 'Placed At'];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantPanelRepository::class);
        return $repository->exportOrders(['search' => $this->search, 'status' => $this->statusFilter])
            ->map(fn(Order $order) => [
                $order->uuid,
                $order->customer?->full_name ?? 'Guest',
                $order->customer?->email ?? '',
                $order->items_count,
                number_format((float) $order->grand_total, 2),
                number_format((float) $order->discount_percentage, 2),
                number_format((float) $order->resolved_shipping_charge, 2),
                number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
                $order->status->label(),
                str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString(),
                $order->paid ? 'Paid' : 'Unpaid',
                $order->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }
}
