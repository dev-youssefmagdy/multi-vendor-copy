<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Enums\ManufacturingPaymentRequestStatus;
use App\Enums\ManufacturingRequestStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\ManufacturingPaymentRequest;
use App\Models\ManufacturingRequest;
use App\Models\ManufacturingRequestMessage;
use App\Services\TenantNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ManufacturingRequestDetail extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;

    #[Locked]
    public int $requestId;

    // Status update
    public string $newStatus = '';
    public string $adminNotes = '';

    // Chat
    public string $chatMessage = '';

    // Payment request creation
    public bool $showPaymentForm = false;
    public string $paymentLabel = '';
    public string $paymentAmount = '';
    public string $paymentCurrency = 'USD';
    public string $paymentNotes = '';

    public function mount(int $id): void
    {
        $this->authorizePermission('manufacturing.view');
        $this->requestId = $id;
        $request = $this->getRequest();
        $this->newStatus = $request->status->value;
        $this->adminNotes = (string) $request->admin_notes;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getRequest(): ManufacturingRequest
    {
        return ManufacturingRequest::with(['tenant', 'messages', 'paymentRequests'])
            ->findOrFail($this->requestId);
    }

    // ── Status Update ─────────────────────────────────────────────────────────

    public function updateStatus(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('manufacturing.manage');

        $this->validate([
            'newStatus' => 'required|in:' . implode(',', array_column(ManufacturingRequestStatus::cases(), 'value')),
            'adminNotes' => 'nullable|string|max:2000',
        ]);

        $request = ManufacturingRequest::findOrFail($this->requestId);
        $oldStatus = $request->status;
        $newStatus = ManufacturingRequestStatus::from($this->newStatus);

        $request->update([
            'status' => $newStatus->value,
            'admin_notes' => $this->adminNotes ?: null,
        ]);

        if ($oldStatus !== $newStatus) {
            $notifier->notifyById(
                $request->tenant_id,
                'manufacturing_status',
                'Manufacturing Request Updated',
                'Your manufacturing request for "' . $request->product_name . '" is now: ' . $newStatus->label(),
                ['request_id' => $request->id, 'status' => $newStatus->value]
            );
        }

        $this->toast('Status updated and tenant notified.');
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function sendMessage(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('manufacturing.manage');

        $this->validate(['chatMessage' => 'required|string|max:2000']);

        $adminUser = auth('admin')->user();

        ManufacturingRequestMessage::create([
            'manufacturing_request_id' => $this->requestId,
            'sender_type' => 'admin',
            'sender_name' => $adminUser?->name ?? 'Admin',
            'message' => trim($this->chatMessage),
        ]);

        $request = ManufacturingRequest::findOrFail($this->requestId);

        $notifier->notifyById(
            $request->tenant_id,
            'manufacturing_chat',
            'New Message on Manufacturing Request',
            'The admin sent a message on your manufacturing request for "' . $request->product_name . '".',
            ['request_id' => $request->id]
        );

        $this->chatMessage = '';
        $this->dispatch('chat-scrolled');
    }

    // ── Payment Requests ─────────────────────────────────────────────────────

    public function togglePaymentForm(): void
    {
        $this->showPaymentForm = !$this->showPaymentForm;
        if (!$this->showPaymentForm) {
            $this->resetPaymentForm();
        }
    }

    public function createPaymentRequest(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('manufacturing.manage');

        $this->validate([
            'paymentLabel' => 'required|string|max:255',
            'paymentAmount' => 'required|numeric|min:0.01|max:9999999',
            'paymentCurrency' => 'required|string|max:10',
            'paymentNotes' => 'nullable|string|max:1000',
        ]);

        $request = ManufacturingRequest::findOrFail($this->requestId);

        ManufacturingPaymentRequest::create([
            'manufacturing_request_id' => $this->requestId,
            'tenant_id' => $request->tenant_id,
            'label' => $this->paymentLabel,
            'amount' => (float) $this->paymentAmount,
            'currency' => strtoupper($this->paymentCurrency),
            'status' => ManufacturingPaymentRequestStatus::Pending->value,
            'notes' => $this->paymentNotes ?: null,
        ]);

        $notifier->notifyById(
            $request->tenant_id,
            'manufacturing_payment',
            'Payment Request Created',
            'A payment request of ' . $this->paymentCurrency . ' ' . number_format((float) $this->paymentAmount, 2) . ' has been issued for your manufacturing request "' . $request->product_name . '".',
            ['request_id' => $request->id]
        );

        $this->resetPaymentForm();
        $this->showPaymentForm = false;
        $this->toast('Payment request created and tenant notified.');
    }

    public function cancelPaymentRequest(int $paymentRequestId, TenantNotificationService $notifier): void
    {
        $this->authorizePermission('manufacturing.manage');

        $paymentRequest = ManufacturingPaymentRequest::findOrFail($paymentRequestId);

        if ($paymentRequest->status !== ManufacturingPaymentRequestStatus::Pending) {
            $this->toast('Only pending payment requests can be cancelled.', 'error');
            return;
        }

        $paymentRequest->update(['status' => ManufacturingPaymentRequestStatus::Cancelled->value]);

        $notifier->notifyById(
            $paymentRequest->tenant_id,
            'manufacturing_payment',
            'Payment Request Cancelled',
            'A payment request of ' . $paymentRequest->currency . ' ' . number_format((float) $paymentRequest->amount, 2) . ' for your manufacturing request has been cancelled.',
            []
        );

        $this->toast('Payment request cancelled.');
    }

    private function resetPaymentForm(): void
    {
        $this->paymentLabel = '';
        $this->paymentAmount = '';
        $this->paymentCurrency = 'USD';
        $this->paymentNotes = '';
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $request = $this->getRequest();

        return view('livewire.admin.manufacturing.manufacturing-request-detail', [
            'request' => $request,
            'messages' => $request->messages()->oldest()->get(),
            'paymentRequests' => $request->paymentRequests()->latest()->get(),
            'statusOptions' => ManufacturingRequestStatus::cases(),
            'canManage' => $this->hasPermission('manufacturing.manage'),
            'showPaymentForm' => $this->showPaymentForm,
        ]);
    }
}
