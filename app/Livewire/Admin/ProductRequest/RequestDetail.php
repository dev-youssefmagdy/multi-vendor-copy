<?php

namespace App\Livewire\Admin\ProductRequest;

use App\Enums\ProductRequestStatus;
use App\Events\ProductRequestMessageSent;
use App\Events\ProductRequestStatusChanged;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\ProductRequest;
use App\Models\ProductRequestMessage;
use App\Services\TenantNotificationService;
use Livewire\WithFileUploads;

class RequestDetail extends AdminPage
{
    use InteractsWithAdminUi, WithFileUploads;

    public int $requestId = 0;
    public array $request = [];
    public string $reply = '';
    public string $statusSelection = '';
    public string $priority = 'normal';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $replyFiles = [];

    public function mount(int $requestId): void
    {
        $this->authorizeAnyPermission(['catalog.product-requests.view', 'catalog.product-requests.manage']);

        $this->requestId = $requestId;

        $req = ProductRequest::with('messages')->find($requestId);
        abort_if(!$req, 404);

        if ($req->admin_has_unread) {
            $req->update(['admin_has_unread' => false]);
        }

        $this->request = $this->presentRequest($req);
        $this->statusSelection = $req->status->value;
        $this->priority = $req->priority;
    }

    public function sendReply(TenantNotificationService $notificationService): void
    {
        $this->authorizePermission('catalog.product-requests.manage');

        $this->validate([
            'reply' => ['required', 'string', 'min:2', 'max:5000'],
            'replyFiles' => ['array', 'max:5'],
            'replyFiles.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx'],
        ]);

        $req = ProductRequest::find($this->requestId);
        abort_if(!$req, 404);

        $senderName = $this->adminUser()?->name ?? 'Neozena Team';

        $storedPaths = [];
        foreach ($this->replyFiles as $file) {
            $storedPaths[] = $file->store("product-requests/{$req->tenant_id}/messages", 'public');
        }

        ProductRequestMessage::create([
            'product_request_id' => $req->id,
            'sender_type' => 'admin',
            'sender_name' => $senderName,
            'body' => $this->reply,
            'attachments' => $storedPaths ?: null,
        ]);

        $req->update([
            'tenant_has_unread' => true,
            'last_reply_at' => now(),
        ]);

        $notificationService->notifyById(
            $req->tenant_id,
            'product_request',
            'New reply on your product request',
            "{$senderName} replied to your product request: {$req->title}",
            ['request_id' => $req->id]
        );

        event(new ProductRequestMessageSent(
            requestId: $req->id,
            tenantId: $req->tenant_id,
            senderType: 'admin',
            senderName: $senderName,
            body: $this->reply,
            sentAt: now()->toIso8601String(),
        ));

        $this->request = $this->presentRequest($req->fresh('messages'));
        $this->reply = '';
        $this->replyFiles = [];
        $this->toast('Reply sent.');
    }

    public function updateStatus(TenantNotificationService $notificationService): void
    {
        $this->authorizePermission('catalog.product-requests.manage');

        $this->validate([
            'statusSelection' => ['required', 'string', 'in:' . implode(',', array_column(ProductRequestStatus::cases(), 'value'))],
        ]);

        $req = ProductRequest::find($this->requestId);
        abort_if(!$req, 404);

        $newStatus = ProductRequestStatus::from($this->statusSelection);
        $req->update(['status' => $newStatus->value, 'priority' => $this->priority]);

        $notificationService->notifyById(
            $req->tenant_id,
            'product_request',
            'Product request status updated',
            "Your request \"{$req->title}\" is now: {$newStatus->label()}",
            ['request_id' => $req->id]
        );

        event(new ProductRequestStatusChanged(
            requestId: $req->id,
            tenantId: $req->tenant_id,
            status: $newStatus,
        ));

        $this->request = $this->presentRequest($req->fresh('messages'));
        $this->toast('Status updated to ' . $newStatus->label() . '.');
    }

    public function refreshRequest(): void
    {
        $req = ProductRequest::with('messages')->find($this->requestId);

        if (!$req) {
            return;
        }

        if ($req->admin_has_unread) {
            $req->update(['admin_has_unread' => false]);
        }

        $this->request = $this->presentRequest($req);
    }

    private function presentRequest(ProductRequest $r): array
    {
        return [
            'id' => $r->id,
            'tenant_id' => $r->tenant_id,
            'title' => $r->title,
            'description' => $r->description,
            'attachments' => $r->attachments ?? [],
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'status_badge' => $r->status->badgeClass(),
            'status_step' => $r->status->stepNumber(),
            'priority' => $r->priority,
            'last_reply_at' => $r->last_reply_at?->diffForHumans(),
            'created_at' => $r->created_at->format('M d, Y H:i'),
            'messages' => $r->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'body' => $m->body,
                'attachments' => $m->attachments ?? [],
                'sent_at' => $m->created_at->format('M d, Y H:i'),
                'is_mine' => $m->sender_type === 'admin',
            ])->all(),
        ];
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->request['title'] ?? 'Product Request',
            'badge' => 'Catalog',
            'description' => '',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.product-request.detail';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'request' => $this->request,
            'statusOptions' => ProductRequestStatus::options(),
            'priorityOptions' => ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'],
        ]);
    }
}
