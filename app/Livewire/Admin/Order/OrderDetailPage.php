<?php

namespace App\Livewire\Admin\Order;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\OrderReturn;
use App\Repositories\OrderRepository;
use App\Services\Admin\OrderFulfillmentService;

class OrderDetailPage extends AdminPage
{
    use InteractsWithAdminUi;

    public string $tenantId = '';
    public string $orderNumber = '';
    public array $order = [];
    public string $trackingNumber = '';

    public bool $showReturnModal = false;
    public string $returnReason = '';
    public string $returnNotes = '';

    public function mount(string $tenantId, string $orderNumber): void
    {
        $this->authorizeAnyPermission(['sales.orders.view', 'sales.orders.manage']);
        $this->tenantId = $tenantId;
        $this->orderNumber = $orderNumber;

        $record = app(OrderRepository::class)->find($tenantId, $orderNumber);

        abort_if(!$record, 404);

        $this->order = app(OrderRepository::class)->orderDetail($record);
        $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
    }

    public function updateTrackingNumber(OrderFulfillmentService $service): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $this->validate([
            'trackingNumber' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->updateTrackingNumber($this->tenantId, $this->orderNumber, $this->trackingNumber);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $record = app(OrderRepository::class)->find($this->tenantId, $this->orderNumber);
        if ($record) {
            $this->order = app(OrderRepository::class)->orderDetail($record);
            $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
        }

        session()->flash('status', 'Tracking number updated successfully.');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->order['uuid'] ?? 'Order Details',
            'badge' => $this->order['tenant']['name'] ?? 'Central',
            'description' => 'Full order detail view — financials, line items, shipping address, and gateway payload.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.order.order-detail-page';
    }

    public function createReturn(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validate([
            'returnReason' => ['required', 'string', 'min:10', 'max:1000'],
            'returnNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = OrderReturn::where('tenant_id', $this->tenantId)
            ->where('order_number', $this->orderNumber)
            ->whereIn('status', [ReturnStatus::Pending->value, ReturnStatus::Approved->value])
            ->exists();

        if ($existing) {
            $this->toast('An active return request already exists for this order.', 'error');
            return;
        }

        $returnRecord = OrderReturn::create([
            'tenant_id' => $this->tenantId,
            'order_number' => $this->orderNumber,
            'status' => ReturnStatus::Pending,
            'reason' => $this->returnReason,
            'customer_notes' => $this->returnNotes ?: null,
        ]);

        $this->showReturnModal = false;
        $this->returnReason = '';
        $this->returnNotes = '';
        $this->toast('Return request created successfully.');
        $this->redirect(route('admin.orders.returns.show', $returnRecord->id));
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'order' => $this->order,
        ]);
    }
}
