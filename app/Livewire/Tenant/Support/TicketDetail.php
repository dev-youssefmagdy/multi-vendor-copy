<?php

namespace App\Livewire\Tenant\Support;

use App\Events\SupportTicketMessageSent;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\SupportTicket;
use App\Services\AdminNotificationService;

class TicketDetail extends TenantPage
{
    use InteractsWithTenantUi;

    public int $ticketId = 0;
    public array $ticket = [];
    public string $reply = '';

    public function mount(int $ticketId): void
    {
        $tenantId = tenant()->getTenantKey();
        $this->ticketId = $ticketId;

        $ticket = tenancy()->central(function () use ($ticketId, $tenantId) {
            $ticket = SupportTicket::forTenant($tenantId)->with('messages')->find($ticketId);

            if ($ticket && $ticket->tenant_has_unread) {
                $ticket->update(['tenant_has_unread' => false]);
            }

            return $ticket;
        });

        abort_if(!$ticket, 404);

        $this->ticket = $this->presentTicket($ticket);
    }

    public function sendReply(AdminNotificationService $notificationService): void
    {
        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:5000']]);

        $tenantId = tenant()->getTenantKey();
        $tenant = tenant();
        $tenantName = $tenant->getAttribute('name') ?? $tenant->getAttribute('shop_name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;
        $body = $this->reply;

        $result = tenancy()->central(function () use ($tenantId, $senderName, $body) {
            $ticket = SupportTicket::forTenant($tenantId)->find($this->ticketId);

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

        if ($result === null) {
            abort(404);
        }

        if ($result === 'closed') {
            $this->toast('This ticket is closed and can no longer receive replies.', 'error');
            return;
        }

        $notificationService->notify(
            'support_ticket',
            'New support ticket reply',
            "{$tenantName} replied to ticket: {$result->subject}",
            ['ticket_id' => $result->id, 'tenant_id' => $tenantId]
        );

        event(new SupportTicketMessageSent(
            ticketId: $result->id,
            tenantId: $tenantId,
            senderType: 'tenant',
            senderName: $senderName,
            body: $body,
            sentAt: now()->toIso8601String(),
        ));

        $this->ticket = $this->presentTicket($result);
        $this->reply = '';
        $this->toast('Reply sent.');
    }

    public function refreshTicket(): void
    {
        $tenantId = tenant()->getTenantKey();

        $ticket = tenancy()->central(function () use ($tenantId) {
            $ticket = SupportTicket::forTenant($tenantId)->with('messages')->find($this->ticketId);

            if ($ticket && $ticket->tenant_has_unread) {
                $ticket->update(['tenant_has_unread' => false]);
            }

            return $ticket;
        });

        if ($ticket) {
            $this->ticket = $this->presentTicket($ticket);
        }
    }

    protected function presentTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'is_closed' => in_array($ticket->status, ['resolved', 'closed'], true),
            'messages' => $ticket->messages->map(fn ($message) => [
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_name,
                'body' => $message->body,
                'created_at' => $message->created_at?->format('M d, Y H:i'),
            ])->all(),
        ];
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->ticket['subject'] ?? 'Ticket Details',
            'badge' => 'Help Desk',
            'description' => 'Conversation history with the marketplace admin team.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.support.ticket-detail';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'ticket' => $this->ticket,
            'statusOptions' => SupportTicket::statusOptions(),
            'priorityOptions' => SupportTicket::priorityOptions(),
            'categoryOptions' => SupportTicket::categoryOptions(),
        ]);
    }
}
