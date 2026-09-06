<?php

namespace App\Livewire\Tenant\ProductRequest;

use App\Enums\ProductRequestStatus;
use App\Events\ProductRequestMessageSent;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ProductRequest;
use App\Models\ProductRequestMessage;
use App\Services\AdminNotificationService;
use Livewire\WithFileUploads;

class RequestDetail extends TenantPage
{
    use InteractsWithTenantUi, WithFileUploads;

    public int    $requestId = 0;
    public array  $request   = [];
    public string $reply     = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array  $replyFiles = [];

    public function mount(int $requestId): void
    {
        $tenantId        = tenant()->getTenantKey();
        $this->requestId = $requestId;

        $req = tenancy()->central(function () use ($requestId, $tenantId) {
            $r = ProductRequest::forTenant($tenantId)->with('messages')->find($requestId);
            if ($r && $r->tenant_has_unread) {
                $r->update(['tenant_has_unread' => false]);
            }
            return $r;
        });

        abort_if(!$req, 404);
        $this->request = $this->presentRequest($req);
    }

    public function sendReply(AdminNotificationService $notificationService): void
    {
        $this->validate([
            'reply'        => ['required', 'string', 'min:2', 'max:5000'],
            'replyFiles'   => ['array', 'max:5'],
            'replyFiles.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx'],
        ]);

        $tenantId   = tenant()->getTenantKey();
        $tenant     = tenant();
        $tenantName = $tenant->getAttribute('shop_name') ?? $tenant->getAttribute('name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;
        $body       = $this->reply;

        $storedPaths = [];
        foreach ($this->replyFiles as $file) {
            $storedPaths[] = $file->store("product-requests/{$tenantId}/messages", 'public');
        }

        $req = tenancy()->central(function () use ($tenantId, $senderName, $body, $storedPaths) {
            $r = ProductRequest::forTenant($tenantId)->find($this->requestId);
            if (!$r) {
                return null;
            }

            if (in_array($r->status->value, [
                ProductRequestStatus::Completed->value,
                ProductRequestStatus::Rejected->value,
            ], true)) {
                return 'closed';
            }

            ProductRequestMessage::create([
                'product_request_id' => $r->id,
                'sender_type'        => 'tenant',
                'sender_name'        => $senderName,
                'body'               => $body,
                'attachments'        => $storedPaths ?: null,
            ]);

            $r->update([
                'admin_has_unread' => true,
                'last_reply_at'    => now(),
            ]);

            return $r->fresh('messages');
        });

        if ($req === null) {
            abort(404);
        }
        if ($req === 'closed') {
            $this->toast('This request is closed.', 'error');
            return;
        }

        event(new ProductRequestMessageSent(
            requestId:  $this->requestId,
            tenantId:   $tenantId,
            senderType: 'tenant',
            senderName: $senderName,
            body:       $body,
            sentAt:     now()->toIso8601String(),
        ));

        $this->request    = $this->presentRequest($req);
        $this->reply      = '';
        $this->replyFiles = [];
        $this->toast('Reply sent.');
    }

    private function presentRequest(ProductRequest $r): array
    {
        return [
            'id'                => $r->id,
            'title'             => $r->title,
            'description'       => $r->description,
            'attachments'       => $r->attachments ?? [],
            'status'            => $r->status->value,
            'status_label'      => $r->status->label(),
            'status_badge'      => $r->status->badgeClass(),
            'status_step'       => $r->status->stepNumber(),
            'priority'          => $r->priority,
            'last_reply_at'     => $r->last_reply_at?->diffForHumans(),
            'created_at'        => $r->created_at->format('M d, Y H:i'),
            'tenant_has_unread' => $r->tenant_has_unread,
            'messages'          => $r->messages->map(fn($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'body'        => $m->body,
                'attachments' => $m->attachments ?? [],
                'sent_at'     => $m->created_at->format('M d, Y H:i'),
                'is_mine'     => $m->sender_type === 'tenant',
            ])->all(),
        ];
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => $this->request['title'] ?? 'Product Request',
            'badge'       => 'Catalog',
            'description' => '',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.product-request.detail';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'request' => $this->request,
        ]);
    }
}
