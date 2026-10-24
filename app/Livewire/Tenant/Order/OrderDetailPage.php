<?php

namespace App\Livewire\Tenant\Order;

use App\Enums\OrderShippingStatus;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Tenant\Base\TenantPage;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\OrderLifecycleService;
use InvalidArgumentException;

class OrderDetailPage extends TenantPage
{
    use InteractsWithAdminUi;

    public int $orderId = 0;
    public array $order = [];
    public string $selectedShippingStatus = '';

    public function mount(int $orderId): void
    {
        $record = Order::query()->findOrFail($orderId);
        $this->orderId = $orderId;
        $this->order = app(TenantPanelRepository::class)->orderDetail($record);
        $this->selectedShippingStatus = $this->order['shipping_status_value'] ?? '';
    }

    public function updateShippingStatus(): void
    {
        if (!($this->order['can_update_shipping'] ?? false)) {
            $this->toast(__('Shipping status can only be updated for your own products orders.'), 'error');
            return;
        }

        $status = OrderShippingStatus::tryFrom($this->selectedShippingStatus);

        if (!$status) {
            $this->toast(__('Invalid shipping status selected.'), 'error');
            return;
        }

        $order = Order::query()->findOrFail($this->orderId);

        try {
            $updated = app(OrderLifecycleService::class)->updateShippingStatus($order, $status);
        } catch (InvalidArgumentException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }

        $this->order = app(TenantPanelRepository::class)->orderDetail($updated);
        $this->selectedShippingStatus = $this->order['shipping_status_value'] ?? '';

        $this->toast(__('Shipping status updated successfully.'), 'success');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->order['uuid'] ?? 'Order Details',
            'badge' => 'Sales',
            'description' => 'Full order detail — financials, line items, shipping address, and gateway payload.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.order.order-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'order' => $this->order,
            'shippingStatuses' => OrderShippingStatus::cases(),
        ]);
    }
}
