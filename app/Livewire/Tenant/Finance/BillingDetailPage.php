<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\TenantPage;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;

class BillingDetailPage extends TenantPage
{
    public int $orderId = 0;
    public array $order = [];

    public function mount(int $orderId): void
    {
        $record = Order::query()->findOrFail($orderId);
        $this->orderId = $orderId;
        $this->order = app(TenantPanelRepository::class)->orderDetail($record);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->order['uuid'] ?? 'Billing Details',
            'badge' => 'Finance',
            'description' => 'Full billing detail — payment state, gateway payload, financials, and order line items.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.finance.billing-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'order' => $this->order,
        ]);
    }
}
