<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $ticketId,
        public readonly string $tenantId,
        public readonly string $senderType,
        public readonly string $senderName,
        public readonly string $body,
        public readonly string $sentAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("admin.support.{$this->ticketId}"),
            new PrivateChannel("tenant.{$this->tenantId}.support"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'sender_type' => $this->senderType,
            'sender_name' => $this->senderName,
            'body' => $this->body,
            'sent_at' => $this->sentAt,
        ];
    }
}
