<?php

namespace App\Livewire\Admin\Support;

use App\Events\SupportTicketMessageSent;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\SupportTicket;
use App\Services\TenantNotificationService;

class TicketDetail extends AdminPage
{
    use InteractsWithAdminUi;

    public int $ticketId = 0;
    public array $ticket = [];
    public string $reply = '';
    public string $statusSelection = '';

    public function mount(int $ticketId): void
    {
        $this->authorizeAnyPermission(['support.tickets.view', 'support.tickets.manage']);

        $this->ticketId = $ticketId;

        $ticket = SupportTicket::with('messages')->find($ticketId);

        abort_if(!$ticket, 404);

        if ($ticket->admin_has_unread) {
            $ticket->update(['admin_has_unread' => false]);
        }

        $this->ticket = $this->presentTicket($ticket);
        $this->statusSelection = $ticket->status;
    }

    public function sendReply(TenantNotificationService $notificationService): void
    {
        $this->authorizePermission('support.tickets.manage');

        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:5000']]);

        $ticket = SupportTicket::find($this->ticketId);
        abort_if(!$ticket, 404);

        $senderName = $this->adminUser()?->name ?? 'Support Team';

        $ticket->messages()->create([
            'sender_type' => 'admin',
            'sender_name' => $senderName,
            'body' => $this->reply,
        ]);

        $ticket->update([
            'tenant_has_unread' => true,
            'last_reply_at' => now(),
        ]);

        $notificationService->notifyById(
            $ticket->tenant_id,
            'support_ticket',
            'New reply on your support ticket',
            "{$senderName} replied to your ticket: {$ticket->subject}",
            ['ticket_id' => $ticket->id]
        );

        event(new SupportTicketMessageSent(
            ticketId: $ticket->id,
            tenantId: $ticket->tenant_id,
            senderType: 'admin',
            senderName: $senderName,
            body: $this->reply,
            sentAt: now()->toIso8601String(),
        ));

        $this->ticket = $this->presentTicket($ticket->fresh('messages'));
        $this->reply = '';
        $this->toast('Reply sent.');
    }

    public function updateStatus(TenantNotificationService $notificationService): void
    {
        $this->authorizePermission('support.tickets.manage');

        $this->validate(['statusSelection' => ['required', 'string', 'in:' . implode(',', array_keys(SupportTicket::statusOptions()))]]);

        $ticket = SupportTicket::find($this->ticketId);
        abort_if(!$ticket, 404);

        $ticket->update(['status' => $this->statusSelection]);

        $notificationService->notifyById(
            $ticket->tenant_id,
            'support_ticket',
            'Support ticket status updated',
            "Your ticket \"{$ticket->subject}\" is now " . (SupportTicket::statusOptions()[$this->statusSelection] ?? $this->statusSelection),
            ['ticket_id' => $ticket->id]
        );

        $this->ticket = $this->presentTicket($ticket->fresh('messages'));
        $this->toast('Ticket status updated.');
    }

    public function refreshTicket(): void
    {
        $ticket = SupportTicket::with('messages')->find($this->ticketId);

        if (!$ticket) {
            return;
        }

        if ($ticket->admin_has_unread) {
            $ticket->update(['admin_has_unread' => false]);
        }

        $this->ticket = $this->presentTicket($ticket);
    }

    protected function presentTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'tenant_id' => $ticket->tenant_id,
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
            'badge' => $this->ticket['tenant_id'] ?? 'Central',
            'description' => 'Full conversation history and status controls for this support ticket.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.support.ticket-detail';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'ticket' => $this->ticket,
            'statusOptions' => SupportTicket::statusOptions(),
            'priorityOptions' => SupportTicket::priorityOptions(),
            'categoryOptions' => SupportTicket::categoryOptions(),
            'canManage' => $this->hasPermission('support.tickets.manage'),
        ]);
    }
}
