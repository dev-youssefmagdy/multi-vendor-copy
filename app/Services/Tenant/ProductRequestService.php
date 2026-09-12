<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\ProductRequestStatus;
use App\Events\ProductRequestMessageSent;
use App\Models\ProductRequest;
use App\Models\ProductRequestMessage;
use App\Services\AdminNotificationService;
use Illuminate\Http\UploadedFile;

final class ProductRequestService
{
    public function __construct(
        private readonly AdminNotificationService $notifier,
    ) {
    }

    /**
     * Persist a new product request plus its opening message, notify the
     * admin team and broadcast the message — in the same order as the
     * former `CreateRequest::submit()` Livewire method.
     *
     * @param  UploadedFile[]  $files
     */
    public function create(
        string $tenantId,
        string $tenantName,
        string $senderName,
        string $title,
        string $description,
        ?string $productUrl,
        array $files,
    ): ProductRequest {
        $storedPaths = [];
        foreach ($files as $file) {
            $storedPaths[] = $file->store("product-requests/{$tenantId}", 'public');
        }

        $request = tenancy()->central(function () use ($tenantId, $senderName, $title, $description, $productUrl, $storedPaths) {
            $req = ProductRequest::create([
                'tenant_id' => $tenantId,
                'title' => $title,
                'description' => $description,
                'product_url' => $productUrl ?: null,
                'attachments' => $storedPaths ?: null,
                'status' => 'pending',
                'admin_has_unread' => true,
                'last_reply_at' => now(),
            ]);

            ProductRequestMessage::create([
                'product_request_id' => $req->id,
                'sender_type' => 'tenant',
                'sender_name' => $senderName,
                'body' => $description,
                'attachments' => $storedPaths ?: null,
            ]);

            return $req;
        });

        $this->notifier->notify(
            'product_request',
            'New Product Request',
            "{$tenantName} submitted a product request: {$request->title}",
            ['request_id' => $request->id, 'tenant_id' => $tenantId],
        );

        try {
            event(new ProductRequestMessageSent(
                requestId: $request->id,
                tenantId: $tenantId,
                senderType: 'tenant',
                senderName: $senderName,
                body: $description,
                sentAt: now()->toIso8601String(),
            ));
        } catch (\Throwable) {
        }

        return $request;
    }

    /**
     * Persist a tenant-authored reply and broadcast it — in the same order
     * as the former `RequestDetail::sendReply()` Livewire method.
     *
     * @param  UploadedFile[]  $files
     * @return ProductRequest|'closed'|null null when the request no longer exists
     *                                      for this tenant, 'closed' when it can
     *                                      no longer receive replies.
     */
    public function sendMessage(
        int $requestId,
        string $tenantId,
        string $senderName,
        string $body,
        array $files,
    ): ProductRequest|string|null {
        $storedPaths = [];
        foreach ($files as $file) {
            $storedPaths[] = $file->store("product-requests/{$tenantId}/messages", 'public');
        }

        $result = tenancy()->central(function () use ($requestId, $tenantId, $senderName, $body, $storedPaths) {
            $r = ProductRequest::forTenant($tenantId)->find($requestId);
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
                'sender_type' => 'tenant',
                'sender_name' => $senderName,
                'body' => $body,
                'attachments' => $storedPaths ?: null,
            ]);

            $r->update([
                'admin_has_unread' => true,
                'last_reply_at' => now(),
            ]);

            return $r->fresh('messages');
        });

        if ($result instanceof ProductRequest) {
            try {
                event(new ProductRequestMessageSent(
                    requestId: $requestId,
                    tenantId: $tenantId,
                    senderType: 'tenant',
                    senderName: $senderName,
                    body: $body,
                    sentAt: now()->toIso8601String(),
                ));
            } catch (\Throwable) {
            }
        }

        return $result;
    }
}
