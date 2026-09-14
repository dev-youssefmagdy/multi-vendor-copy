<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\SupportTicketMessageSent;
use App\Models\SupportTicket;
use App\Services\AdminNotificationService;

final class SupportTicketService
{
    public function __construct(
        private readonly AdminNotificationService $notifier,
    ) {
    }

    /**
     * Persist a new support ticket plus its opening message, notify the
     * admin team and broadcast the message — in the same order as the
     * former `CreateTicket::submit()` Livewire method.
     */
    public function create(
        string $tenantId,
        string $tenantName,
        string $senderName,
        string $subject,
        string $category,
        string $priority,
        string $body,
    ): SupportTicket {
        $ticket = tenancy()->central(function () use ($tenantId, $senderName, $subject, $category, $priority, $body) {
            $ticket = SupportTicket::create([
                'tenant_id' => $tenantId,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => 'open',
                'admin_has_unread' => true,
                'last_reply_at' => now(),
            ]);

            $ticket->messages()->create([
                'sender_type' => 'tenant',
                'sender_name' => $senderName,
                'body' => $body,
            ]);

            return $ticket;
        });

        $this->notifier->notify(
            'support_ticket',
            'New support ticket',
            "{$tenantName} opened a new support ticket: {$ticket->subject}",
            ['ticket_id' => $ticket->id, 'tenant_id' => $tenantId],
        );

        event(new SupportTicketMessageSent(
            ticketId: $ticket->id,
            tenantId: $tenantId,
            senderType: 'tenant',
            senderName: $senderName,
            body: $body,
            sentAt: now()->toIso8601String(),
        ));

        return $ticket;
    }

    /**
     * Persist a tenant-authored reply and broadcast it — in the same order
     * as the former `TicketDetail::sendReply()` Livewire method.
     *
     * @return SupportTicket|'closed'|null null when the ticket no longer exists
     *                                     for this tenant, 'closed' when it can
     *                                     no longer receive replies.
     */
    public function sendMessage(
        int $ticketId,
        string $tenantId,
        string $tenantName,
        string $senderName,
        string $body,
    ): SupportTicket|string|null {
        $result = tenancy()->central(function () use ($ticketId, $tenantId, $senderName, $body) {
            $ticket = SupportTicket::forTenant($tenantId)->find($ticketId);

            if (!$ticket) {
                return null;
            }

            if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                return 'closed';
            }

            $ticket->messages()->create([
                'sender_type' => 'tenant',
                'sender_name' => $senderName,
                'body' => $body,
            ]);

            $ticket->update([
                'admin_has_unread' => true,
                'last_reply_at' => now(),
                'status' => 'open',
            ]);

            return $ticket->fresh('messages');
        });

        if ($result instanceof SupportTicket) {
            $this->notifier->notify(
                'support_ticket',
                'New support ticket reply',
                "{$tenantName} replied to ticket: {$result->subject}",
                ['ticket_id' => $result->id, 'tenant_id' => $tenantId],
            );

            event(new SupportTicketMessageSent(
                ticketId: $result->id,
                tenantId: $tenantId,
                senderType: 'tenant',
                senderName: $senderName,
                body: $body,
                sentAt: now()->toIso8601String(),
            ));
        }

        return $result;
    }
}
