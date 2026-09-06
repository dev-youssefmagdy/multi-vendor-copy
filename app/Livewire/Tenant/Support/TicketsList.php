<?php

namespace App\Livewire\Tenant\Support;

use App\Livewire\Tenant\Base\ListPage;
use App\Models\SupportTicket;
use Livewire\WithPagination;

class TicketsList extends ListPage
{
    use WithPagination;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Support Tickets',
            'badge' => 'Help Desk',
            'description' => 'Raise and track support requests with the marketplace admin team.',
            'actionUrl' => route('tenant.support.create'),
            'actionLabel' => 'New Ticket',
            'tableTitle' => 'Your Tickets',
            'headers' => ['Subject', 'Category', 'Priority', 'Status', 'Last Reply', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->getTenantKey();

        $records = tenancy()->central(fn () => SupportTicket::forTenant($tenantId)
            ->orderByDesc('last_reply_at')
            ->orderByDesc('id')
            ->paginate(15));

        $statusOptions = SupportTicket::statusOptions();
        $priorityOptions = SupportTicket::priorityOptions();
        $categoryOptions = SupportTicket::categoryOptions();

        return array_merge(parent::pageData(), [
            'actionLabel' => 'New Ticket',
            'filterFields' => [],
            'statisticsGridClass' => 'g-stats3',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Tickets', 'value' => $records->total(), 'format' => 'number', 'caption' => 'All tickets you have raised', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Open', 'value' => $records->getCollection()->whereIn('status', ['open', 'in_progress'])->count(), 'format' => 'number', 'caption' => 'Awaiting resolution', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Unread Replies', 'value' => $records->getCollection()->where('tenant_has_unread', true)->count(), 'format' => 'number', 'caption' => 'Tickets with a new admin reply', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'rows' => collect($records->items())->map(fn (SupportTicket $ticket) => [
                '<div class="entity-title">' . e($ticket->subject) . ($ticket->tenant_has_unread ? ' <span class="badge badge-amber">New</span>' : '') . '</div>',
                '<span class="badge badge-cyan">' . e($categoryOptions[$ticket->category] ?? ucfirst($ticket->category)) . '</span>',
                '<span class="badge badge-cyan">' . e($priorityOptions[$ticket->priority] ?? ucfirst($ticket->priority)) . '</span>',
                '<span class="badge ' . match ($ticket->status) { 'resolved', 'closed' => 'badge-green', 'in_progress' => 'badge-amber', default => 'badge-cyan' } . '">' . e($statusOptions[$ticket->status] ?? ucfirst($ticket->status)) . '</span>',
                $ticket->last_reply_at ? e($ticket->last_reply_at->format('M d, Y H:i')) : '—',
                '<a href="' . route('tenant.support.show', $ticket->id) . '" class="btn btn-secondary btn-sm">View</a>',
            ])->all(),
            'records' => $records,
            'tableDescription' => $records->total() . ' support tickets on file.',
            'emptyTitle' => 'No support tickets yet',
            'emptyCopy' => 'Raise a new ticket if you need help from the marketplace team.',
        ]);
    }
}
