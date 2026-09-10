<?php

namespace App\Livewire\Admin\BrandRequest;

use App\Enums\BrandPaymentRequestStatus;
use App\Enums\BrandRequestStatus;
use App\Events\BrandRequestMessageSent;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\BrandPaymentRequest;
use App\Models\BrandRequest;
use App\Models\BrandRequestMessage;
use App\Services\TenantNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BrandRequestDetail extends Component
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
        $this->authorizePermission('brand-requests.view');
        $this->requestId = $id;
        $request = $this->getRequest();
        $this->newStatus = $request->status->value;
        $this->adminNotes = (string) $request->admin_notes;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getRequest(): BrandRequest
    {
        return BrandRequest::with(['tenant', 'messages', 'paymentRequests'])
            ->findOrFail($this->requestId);
    }

    // ── Status Update ─────────────────────────────────────────────────────────

    public function updateStatus(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('brand-requests.manage');

        $this->validate([
            'newStatus' => 'required|in:' . implode(',', array_column(BrandRequestStatus::cases(), 'value')),
            'adminNotes' => 'nullable|string|max:2000',
        ]);

        $request = BrandRequest::findOrFail($this->requestId);
        $oldStatus = $request->status;
        $newStatus = BrandRequestStatus::from($this->newStatus);

        $request->update([
            'status' => $newStatus->value,
            'admin_notes' => $this->adminNotes ?: null,
        ]);

        if ($oldStatus !== $newStatus) {
            $notifier->notifyById(
                $request->tenant_id,
                'brand_request_status',
                'Brand Request Updated',
                'Your brand request "' . $request->title . '" is now: ' . $newStatus->label(),
                ['request_id' => $request->id, 'status' => $newStatus->value]
            );
        }

        $this->toast('Status updated and tenant notified.');
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function sendMessage(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('brand-requests.manage');

        $this->validate(['chatMessage' => 'required|string|max:2000']);

        $adminUser = auth('admin')->user();
        $senderName = $adminUser?->name ?? 'Admin';
        $body = trim($this->chatMessage);

        BrandRequestMessage::create([
            'brand_request_id' => $this->requestId,
            'sender_type' => 'admin',
            'sender_name' => $senderName,
            'message' => $body,
        ]);

        $request = BrandRequest::findOrFail($this->requestId);

        $notifier->notifyById(
            $request->tenant_id,
            'brand_request_chat',
            'New Message on Brand Request',
            'The admin sent a message on your brand request "' . $request->title . '".',
            ['request_id' => $request->id]
        );

        try {
            event(new BrandRequestMessageSent(
                requestId: $request->id,
                tenantId: $request->tenant_id,
                senderType: 'admin',
                senderName: $senderName,
                body: $body,
                sentAt: now()->toIso8601String(),
            ));
        } catch (\Throwable) {
        }

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
        $this->authorizePermission('brand-requests.manage');

        $this->validate([
            'paymentLabel' => 'required|string|max:255',
            'paymentAmount' => 'required|numeric|min:0.01|max:9999999',
            'paymentCurrency' => 'required|string|max:10',
            'paymentNotes' => 'nullable|string|max:1000',
        ]);

        $request = BrandRequest::findOrFail($this->requestId);

        BrandPaymentRequest::create([
            'brand_request_id' => $this->requestId,
            'tenant_id' => $request->tenant_id,
            'label' => $this->paymentLabel,
            'amount' => (float) $this->paymentAmount,
            'currency' => strtoupper($this->paymentCurrency),
            'status' => BrandPaymentRequestStatus::Pending->value,
            'notes' => $this->paymentNotes ?: null,
        ]);

        $notifier->notifyById(
            $request->tenant_id,
            'brand_request_payment',
            'Payment Request Created',
            'A payment request of ' . $this->paymentCurrency . ' ' . number_format((float) $this->paymentAmount, 2) . ' has been issued for your brand request "' . $request->title . '".',
            ['request_id' => $request->id]
        );

        $this->resetPaymentForm();
        $this->showPaymentForm = false;
        $this->toast('Payment request created and tenant notified.');
    }

    public function cancelPaymentRequest(int $paymentRequestId, TenantNotificationService $notifier): void
    {
        $this->authorizePermission('brand-requests.manage');

        $paymentRequest = BrandPaymentRequest::findOrFail($paymentRequestId);

        if ($paymentRequest->status !== BrandPaymentRequestStatus::Pending) {
            $this->toast('Only pending payment requests can be cancelled.', 'error');
            return;
        }

        $paymentRequest->update(['status' => BrandPaymentRequestStatus::Cancelled->value]);

        $notifier->notifyById(
            $paymentRequest->tenant_id,
            'brand_request_payment',
            'Payment Request Cancelled',
            'A payment request of ' . $paymentRequest->currency . ' ' . number_format((float) $paymentRequest->amount, 2) . ' for your brand request has been cancelled.',
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

        return view('livewire.admin.brand-request.brand-request-detail', [
            'request' => $request,
            'messages' => $request->messages()->oldest()->get(),
            'paymentRequests' => $request->paymentRequests()->latest()->get(),
            'statusOptions' => BrandRequestStatus::cases(),
            'canManage' => $this->hasPermission('brand-requests.manage'),
            'showPaymentForm' => $this->showPaymentForm,
        ]);
    }
}
