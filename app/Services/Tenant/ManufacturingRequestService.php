<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\ManufacturingMessageSent;
use App\Models\ManufacturingRequest;
use App\Models\ManufacturingRequestMessage;
use App\Services\AdminNotificationService;

final class ManufacturingRequestService
{
    /**
     * Persist a tenant-authored chat message on a manufacturing request and
     * broadcast it, in the same order as the original Livewire component:
     * create the message row, then fire the ManufacturingMessageSent event.
     *
     * @return array{id:int,author:string,at:string,body:string,is_me:bool} the rendered
     *         message payload, shaped for x-tenant::chat's `_tenantAppendMessage`.
     */
    public function sendMessage(ManufacturingRequest $request, string $senderName, string $body): array
    {
        $body = trim($body);

        $message = ManufacturingRequestMessage::create([
            'manufacturing_request_id' => $request->id,
            'sender_type' => 'tenant',
            'sender_name' => $senderName,
            'message' => $body,
        ]);

        app(AdminNotificationService::class)->notify(
            'manufacturing',
            'New manufacturing request message',
            sprintf('%s replied on manufacturing request #%d.', $senderName, $request->id),
            ['request_id' => $request->id, 'tenant_id' => (string) tenant('id')],
        );

        try {
            event(new ManufacturingMessageSent(
                requestId: $request->id,
                tenantId: (string) tenant('id'),
                senderType: 'tenant',
                senderName: $senderName,
                body: $body,
                sentAt: $message->created_at?->toIso8601String() ?? now()->toIso8601String(),
            ));
        } catch (\Throwable) {
        }

        return [
            'id' => $message->id,
            'author' => $senderName,
            'at' => $message->created_at?->format('M d, H:i') ?? now()->format('M d, H:i'),
            'body' => $body,
            'is_me' => true,
        ];
    }
}
