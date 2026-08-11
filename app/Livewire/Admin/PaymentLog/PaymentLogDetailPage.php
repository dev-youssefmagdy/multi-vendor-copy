<?php

namespace App\Livewire\Admin\PaymentLog;

use App\Livewire\Admin\Base\AdminPage;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentLogRepository;

class PaymentLogDetailPage extends AdminPage
{
    public string $tenantId = '';
    public string $orderNumber = '';
    public array $log = [];

    public function mount(string $tenantId, string $orderNumber): void
    {
        $this->authorizePermission('billing.payment-logs.view');
        $this->tenantId = $tenantId;
        $this->orderNumber = $orderNumber;

        $record = app(PaymentLogRepository::class)->find($tenantId, $orderNumber);
        abort_if(!$record, 404);

        $this->log = [
            'tenant' => ['id' => $record->tenant_id, 'name' => $record->store_name, 'email' => $record->tenant?->email],
            'order_number' => $record->order_number,
            'customer_name' => $record->customer_name,
            'customer_email' => $record->customer_email,
            'gateway' => $record->gateway,
            'amount' => (float) $record->amount,
            'status' => $record->status->label(),
            'reference' => $record->reference,
            'paid_at' => $record->paid_at,
            'created_at' => $record->created_at,
            'meta' => $record->meta,
            'order' => app(OrderRepository::class)->orderDetail($record->order),
        ];
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->log['order_number'] ?? 'Payment Log',
            'badge' => $this->log['tenant']['name'] ?? 'Central',
            'description' => 'Full payment log detail — gateway payload, order snapshot, and financial breakdown.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.payment-log.payment-log-detail-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'log' => $this->log,
        ]);
    }
}
