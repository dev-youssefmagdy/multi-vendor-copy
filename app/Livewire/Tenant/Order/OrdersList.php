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
            'title' => __('Orders'),
            'badge' => __('Sales'),
            'description' => __('Track tenant orders, payment collection, fulfillment progress, and full order payload details from one queue.'),
'tableTitle' => __('Order Queue'),
            'headers' => [__('Order'), __('Customer'), __('Value'), __('Commission'), __('Status'), __('Gateway'), __('Placed At'), __('Actions')],
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
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Order UUID or customer')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => __('All')] + collect(OrderStatus::cases())->mapWithKeys(fn($status) => [$status->value => __($status->label())])->all()],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => __('Orders'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('Orders in this tenant database.'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Paid'), 'value' => $stats['paid'], 'format' => 'number', 'caption' => __('Orders with successful payment capture.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Processing'), 'value' => $stats['processing'], 'format' => 'number', 'caption' => __('Orders currently moving through fulfillment.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Collected'), 'value' => $stats['collected'], 'format' => 'currency', 'caption' => __('Paid order value collected by the tenant.'), 'dot' => 'dot-violet'],
            ]),
            'rows' => collect($records->items())->map(fn(Order $order) => [
                '<div class="entity-title">' . e($order->uuid) . '</div><div class="entity-subtitle">' . e(__(':items items · :units units', ['items' => $order->items_count, 'units' => $order->items->sum('qty')])) . '</div>',
                '<div class="entity-title">' . e($order->customer?->full_name ?? __('Guest')) . '</div><div class="entity-subtitle">' . e($order->customer?->email ?? __('No email')) . '</div>',
                '<div class="entity-title">' . number_format($order->grand_total, 2) . '</div>'
                . '<div class="entity-subtitle">'
                . ((float) $order->discount_percentage > 0 ? e(__(':percent% off', ['percent' => number_format((float) $order->discount_percentage, 1)])) . ' · ' : '')
                . e(__('ship :amount', ['amount' => number_format($order->resolved_shipping_charge, 2)]))
                . '</div>',
                '<div class="entity-title">' . number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2) . '</div>'
                . '<div class="entity-subtitle">' . e(__('platform :amount', ['amount' => number_format(OrderProfitCalculator::effectiveOwnerProfitForOrder($order), 2)])) . '</div>',
                '<span class="badge badge-cyan">' . e(__($order->status->label())) . '</span>',
                '<div class="entity-title">' . e(/*$order->paymentGateway?->name ?? */ __((string) str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline())) . '</div><div class="entity-subtitle">' . e($order->paid ? __('Paid') : __('Unpaid')) . '</div>',
                e($order->created_at?->format('M d, Y H:i')),
                '<a href="' . route('tenant.orders.show', $order->id) . '" class="btn btn-secondary btn-sm">' . e(__('View')) . '</a>',
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
        return [__('Order UUID'), __('Customer'), __('Customer Email'), __('Items'), __('Grand Total'), __('Discount %'), __('Shipping'), __('Vendor Commission'), __('Status'), __('Payment Method'), __('Paid'), __('Placed At')];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantPanelRepository::class);
        return $repository->exportOrders(['search' => $this->search, 'status' => $this->statusFilter])
            ->map(fn(Order $order) => [
                $order->uuid,
                $order->customer?->full_name ?? __('Guest'),
                $order->customer?->email ?? '',
                $order->items_count,
                number_format((float) $order->grand_total, 2),
                number_format((float) $order->discount_percentage, 2),
                number_format((float) $order->resolved_shipping_charge, 2),
                number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
                __($order->status->label()),
                __((string) str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()),
                $order->paid ? __('Paid') : __('Unpaid'),
                $order->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }
}
