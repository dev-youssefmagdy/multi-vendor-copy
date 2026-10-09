<?php

namespace App\Livewire\Admin\Order;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\ReturnReason;
use App\Exceptions\OrderActionException;
use App\Exceptions\ReturnActionException;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\ManagesRefundActions;
use App\Models\AdminUser;
use App\Models\Refund;
use App\Repositories\OrderRepository;
use App\Services\Admin\OrderAfterSalesService;
use App\Services\Admin\OrderFulfillmentService;
use App\Services\Refunds\RefundActor;
use App\Services\ReturnRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderDetailPage extends AdminPage
{
    use InteractsWithAdminUi;
    use ManagesRefundActions;

    public string $tenantId = '';

    public string $orderNumber = '';

    public array $order = [];

    public string $trackingNumber = '';

    public bool $showReturnModal = false;

    public string $returnReason = '';

    public string $returnNotes = '';

    // Cancel order (two steps: reason → confirm)
    public bool $showCancelModal = false;

    public int $cancelStep = 1;

    public string $cancelReason = '';

    public string $cancelNote = '';

    // Manual refund
    public bool $showRefundModal = false;

    public string $refundAmount = '';

    public string $refundReason = '';

    /** @var array<string, mixed>|null per-request cache of OrderAfterSalesService::orderContext() */
    protected ?array $afterSalesCache = null;

    public function mount(string $tenantId, string $orderNumber): void
    {
        $this->authorizeAnyPermission(['sales.orders.view', 'sales.orders.manage']);
        $this->tenantId = $tenantId;
        $this->orderNumber = $orderNumber;

        $record = app(OrderRepository::class)->find($tenantId, $orderNumber);

        abort_if(! $record, 404);

        $this->order = app(OrderRepository::class)->orderDetail($record);
        $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
    }

    public function updateTrackingNumber(OrderFulfillmentService $service): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        if ($this->shippingLocked()) {
            $this->toast(__('Shipping can no longer be updated for this order.'), 'error');

            return;
        }

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

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        try {
            // Order-status + one-open-request rules; goes straight to the merchant for review.
            $returnRecord = app(ReturnRequestService::class)->createByStaff([
                'tenant_id' => $this->tenantId,
                'order_number' => $this->orderNumber,
                'reason' => ReturnReason::Other,
                'description' => trim($this->returnReason.($this->returnNotes ? "\n\n".$this->returnNotes : '')),
            ], RefundActor::admin($admin?->id, $admin?->name));
        } catch (ReturnActionException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->showReturnModal = false;
        $this->returnReason = '';
        $this->returnNotes = '';
        $this->toast('Return request created successfully.');
        $this->redirect(route('admin.orders.returns.show', $returnRecord->id));
    }

    // ─── Cancel order ───────────────────────────────────────────────────────

    public function openCancelModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $this->resetValidation();
        $this->reset(['cancelStep', 'cancelReason', 'cancelNote']);
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->reset(['cancelStep', 'cancelReason', 'cancelNote']);
        $this->resetValidation();
    }

    /** Step 1 → 2: validate the reason / note, then ask for the final confirmation. */
    public function continueCancel(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validateCancellation();

        $this->cancelStep = 2;
    }

    public function backToCancelReason(): void
    {
        $this->cancelStep = 1;
    }

    public function cancelOrder(OrderAfterSalesService $service): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);
        $this->validateCancellation();

        try {
            $service->cancel($this->tenantId, $this->orderNumber, $this->cancelReason, $this->cancelNote ?: null, $this->adminUser()?->id);
        } catch (OrderActionException $e) {
            // The policy message ("already shipped …", "already cancelled", …) as a toast.
            $this->closeCancelModal();
            $this->refreshOrder();
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->closeCancelModal();
        $this->refreshOrder();
        $this->toast(__('Order cancelled.'));
    }

    private function validateCancellation(): void
    {
        $this->validate([
            'cancelReason' => ['required', 'string', Rule::in(array_keys(CancellationReason::options(CancellationActor::Admin)))],
            'cancelNote' => [
                Rule::requiredIf($this->cancelReason === CancellationReason::Other->value),
                'nullable', 'string', 'max:1000',
            ],
        ], [
            'cancelReason.required' => __('Please choose a cancellation reason.'),
            'cancelReason.in' => __('Please choose a valid cancellation reason.'),
            'cancelNote.required' => __('Please tell us more about the reason for cancelling.'),
        ]);
    }

    // ─── Refunds ────────────────────────────────────────────────────────────

    public function openRefundModal(): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $this->resetValidation();
        $this->refundReason = '';
        $this->refundAmount = number_format((float) ($this->afterSales()['refundable_amount'] ?? 0), 2, '.', '');
        $this->showRefundModal = true;
    }

    public function closeRefundModal(): void
    {
        $this->showRefundModal = false;
        $this->resetValidation();
    }

    /** Manual refund: amount ≤ what is still refundable, reason required. */
    public function issueRefund(OrderAfterSalesService $service): void
    {
        $this->authorizeAnyPermission(['sales.orders.manage']);

        $refundable = round((float) ($this->afterSales()['refundable_amount'] ?? 0), 2);

        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01', 'max:'.max($refundable, 0.01)],
            'refundReason' => ['required', 'string', 'max:255'],
        ], [
            'refundAmount.min' => __('The refund amount must be greater than zero.'),
            'refundAmount.max' => $refundable > 0
                ? __('The refund exceeds the amount that can still be refunded on this order (:amount).', ['amount' => number_format($refundable, 2)])
                : __('There is nothing left to refund on this order.'),
        ]);

        $refund = null;

        if (! $this->attemptRefundAction(function () use ($service, &$refund): void {
            $refund = $service->manualRefund($this->tenantId, $this->orderNumber, (float) $this->refundAmount, $this->refundReason, $this->refundActor());
        })) {
            return;
        }

        $this->closeRefundModal();
        $this->refreshOrder();
        $this->toastForRefund($refund);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    protected function refundsScope(): Builder
    {
        return Refund::query()->forOrder($this->tenantId, $this->orderNumber);
    }

    protected function afterRefundAction(): void
    {
        $this->refreshOrder();
    }

    private function refreshOrder(): void
    {
        $this->afterSalesCache = null;

        $record = app(OrderRepository::class)->find($this->tenantId, $this->orderNumber);

        if ($record) {
            $this->order = app(OrderRepository::class)->orderDetail($record);
            $this->trackingNumber = (string) ($this->order['tracking_number'] ?? '');
        }
    }

    /** Cancelled / rejected / refunded orders have no fulfilment left to control. */
    private function shippingLocked(): bool
    {
        return in_array($this->order['status_value'] ?? null, ['cancelled', 'rejected', 'refunded'], true);
    }

    /** @return array<string, mixed> */
    private function afterSales(): array
    {
        return $this->afterSalesCache ??= app(OrderAfterSalesService::class)->orderContext(
            $this->tenantId,
            $this->orderNumber,
            $this->hasPermission('sales.orders.manage'),
        );
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'order' => $this->order,
            'afterSales' => $this->afterSales(),
            'shippingLocked' => $this->shippingLocked(),
            'canManage' => $this->hasPermission('sales.orders.manage'),
        ]);
    }
}
