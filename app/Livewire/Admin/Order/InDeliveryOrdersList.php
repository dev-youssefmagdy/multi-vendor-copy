<?php

namespace App\Livewire\Admin\Order;

use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Repositories\OrderRepository;
use App\Services\Admin\OrderFulfillmentService;
use Livewire\WithPagination;

class InDeliveryOrdersList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $shippingStatusFilter = 'all';
    public string $selectedShippingStatus = 'pending';
    public bool $showOrderStatusModal = false;
    public string $selectedOrderStatus = '';
    public array $selectedOrderForStatus = [];

    protected function pageView(): string
    {
        return 'livewire.admin.order.in-delivery-orders-list';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'In Delivery Orders',
            'badge' => 'Shipping Queue',
            'description' => 'Review delivery work aggregated from tenant orders, shipping labels, and zone-driven charges.',
            'actionLabel' => 'Dispatch Batch',
            'tableTitle' => 'Orders Pending Delivery',
            'headers' => ['Order', 'Tenant', 'Customer', 'Amount', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(OrderRepository::class);
        $records = $repository->deliveryQueue([
            'search' => $this->search,
            'shipping_status' => $this->shippingStatusFilter,
        ]);
        $stats = $repository->stats();

        $orderStatusOptions = collect(OrderStatus::cases())
            ->mapWithKeys(fn(OrderStatus $s) => [$s->value => $s->label()])
            ->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'orderStatusOptions' => $orderStatusOptions,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order or tenant'],
                ['label' => 'Shipping Status', 'model' => 'shippingStatusFilter', 'type' => 'select', 'options' => ['all' => 'All', 'pending' => 'Pending', 'in_delivery' => 'In Delivery', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']],
            ],
            'filtersNote' => 'Follow the cross-tenant shipping queue from intake to handoff.',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Delivery Queue', 'value' => $records->total(), 'format' => 'number', 'caption' => 'Records in the filtered queue', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'All Shipping Tasks', 'value' => $stats['shipping'], 'format' => 'number', 'caption' => 'Across all statuses', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Paid Orders', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Ready for fulfillment', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gross Revenue', 'value' => $stats['gross_revenue'], 'format' => 'currency', 'caption' => 'Queue-related order value', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn($order) => [
                '<div class="entity-title">' . $order->order_number . '</div><div class="entity-subtitle">' . $order->placed_at?->format('M d, Y') . '</div>',
                '<div class="entity-title">' . e($order->store_name ?? 'Unknown tenant') . '</div><div class="entity-subtitle">' . e($order->tenant?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . e($order->customer_name ?: 'Guest') . '</div><div class="entity-subtitle">' . e($order->customer_email ?: 'No email') . '</div>',
                '<div class="entity-title">$' . number_format((float) $order->total_amount, 2) . '</div><div class="entity-subtitle">$' . number_format((float) $order->shipping_charge, 2) . ' shipping</div>',
                '<div class="entity-title"></div><div class="entity-subtitle"><button type="button" class="link-btn" wire:click=\'openOrderStatusModal(' . json_encode((string) $order->tenant_id) . ', ' . json_encode((string) $order->order_number) . ')\'>' . e($order->status->label()) . '</button></div>',
                '<a href="' . route('admin.shipping.in-delivery.show', [$order->tenant_id, $order->order_number]) . '" class="link-btn">View</a>',
            ])->all(),
            'tableDescription' => $records->total() . ' orders currently need shipping attention.',
            'emptyCopy' => 'No orders are currently waiting in the delivery queue.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShippingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'shippingStatusFilter']);
        $this->shippingStatusFilter = 'all';
        $this->resetPage();
    }

    public function openOrderStatusModal(string $tenantId, string $orderNumber): void
    {
        $order = app(OrderRepository::class)->find($tenantId, $orderNumber);

        if (!$order) {
            return;
        }

        $this->selectedOrderForStatus = app(OrderRepository::class)->orderDetail($order);
        $this->selectedOrderStatus = (string) ($this->selectedOrderForStatus['status_value'] ?? OrderStatus::Pending->value);
        $this->showOrderStatusModal = true;
    }

    public function updateSelectedOrderStatus(OrderFulfillmentService $service): void
    {
        if ($this->selectedOrderForStatus === []) {
            return;
        }

        $orderStatus = OrderStatus::tryFrom($this->selectedOrderStatus);

        if (!$orderStatus) {
            $this->toast('Select a valid order status.', 'error');
            return;
        }

        try {
            $service->updateOrderStatus(
                (string) ($this->selectedOrderForStatus['tenant_id'] ?? ''),
                (string) ($this->selectedOrderForStatus['uuid'] ?? ''),
                $orderStatus,
            );
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');
            return;
        }

        $order = app(OrderRepository::class)->find(
            (string) ($this->selectedOrderForStatus['tenant_id'] ?? ''),
            (string) ($this->selectedOrderForStatus['uuid'] ?? ''),
        );

        if ($order) {
            $this->selectedOrderForStatus = app(OrderRepository::class)->orderDetail($order);
            $this->selectedOrderStatus = (string) ($this->selectedOrderForStatus['status_value'] ?? $orderStatus->value);
        }

        $this->toast('Order status updated successfully.');
    }

    public function closeOrderStatusModal(): void
    {
        $this->showOrderStatusModal = false;
        $this->selectedOrderForStatus = [];
        $this->selectedOrderStatus = '';
    }
}
