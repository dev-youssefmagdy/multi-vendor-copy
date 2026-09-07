<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRequestMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $requestId,
        public readonly string $tenantId,
        public readonly string $senderType,
        public readonly string $senderName,
        public readonly string $body,
        public readonly string $sentAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("admin.product-request.{$this->requestId}"),
            new PrivateChannel("tenant.{$this->tenantId}.product-requests"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'request_id'  => $this->requestId,
            'sender_type' => $this->senderType,
            'sender_name' => $this->senderName,
            'body'        => $this->body,
            'sent_at'     => $this->sentAt,
        ];
    }
}
