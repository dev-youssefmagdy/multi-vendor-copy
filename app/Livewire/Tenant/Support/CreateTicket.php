<?php

namespace App\Livewire\Tenant\Support;

use App\Events\SupportTicketMessageSent;
use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\AdminNotificationService;

class CreateTicket extends ContentPage
{
    use InteractsWithTenantUi;

    public string $subject = '';
    public string $category = 'general';
    public string $priority = 'normal';
    public string $body = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'New Support Ticket',
            'badge' => 'Help Desk',
            'description' => 'Describe your issue and the marketplace admin team will get back to you.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.support.create-ticket';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'statusOptions' => SupportTicket::statusOptions(),
            'priorityOptions' => SupportTicket::priorityOptions(),
            'categoryOptions' => SupportTicket::categoryOptions(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(SupportTicket::categoryOptions()))],
            'priority' => ['required', 'string', 'in:' . implode(',', array_keys(SupportTicket::priorityOptions()))],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function submit(AdminNotificationService $notificationService): void
    {
        $validated = $this->validate();

        $tenant = tenant();
        $tenantId = $tenant->getTenantKey();
        $tenantName = $tenant->getAttribute('name') ?? $tenant->getAttribute('shop_name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;

        $ticket = tenancy()->central(function () use ($validated, $tenantId, $senderName) {
            $ticket = SupportTicket::create([
                'tenant_id' => $tenantId,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'admin_has_unread' => true,
                'last_reply_at' => now(),
            ]);

            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'tenant',
                'sender_name' => $senderName,
                'body' => $validated['body'],
            ]);

            return $ticket;
        });

        $notificationService->notify(
            'support_ticket',
            'New support ticket',
            "{$tenantName} opened a new support ticket: {$ticket->subject}",
            ['ticket_id' => $ticket->id, 'tenant_id' => $tenantId]
        );

        event(new SupportTicketMessageSent(
            ticketId: $ticket->id,
            tenantId: $tenantId,
            senderType: 'tenant',
            senderName: $senderName,
            body: $validated['body'],
            sentAt: now()->toIso8601String(),
        ));

        $this->toast('Support ticket created successfully.');
        $this->redirect(route('tenant.support.show', $ticket->id));
    }
}
