<?php

namespace App\Livewire\Admin\Support;

use App\Livewire\Admin\Base\ListPage;
use App\Models\SupportTicket;
use Livewire\WithPagination;

class TicketsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';

    public function mount(): void
    {
        $this->authorizeAnyPermission(['support.tickets.view', 'support.tickets.manage']);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Support Tickets',
            'badge' => 'All Tenants',
            'description' => 'Track and respond to tenant support requests from one queue.',
            'tableTitle' => 'Support Tickets',
            'headers' => ['Subject', 'Tenant', 'Priority', 'Status', 'Last Reply', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $query = SupportTicket::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhere('tenant_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter !== '') {
            $query->where('priority', $this->priorityFilter);
        }

        $records = $query->orderByDesc('last_reply_at')->orderByDesc('id')->paginate(15);

        $statusOptions = SupportTicket::statusOptions();
        $priorityOptions = SupportTicket::priorityOptions();

        $openCount = SupportTicket::open()->count();
        $unreadCount = SupportTicket::where('admin_has_unread', true)->count();

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Subject or tenant ID'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses'] + $statusOptions],
                ['label' => 'Priority', 'model' => 'priorityFilter', 'type' => 'select', 'options' => ['' => 'All priorities'] + $priorityOptions],
            ],
            'statisticsGridClass' => 'g-stats3',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Tickets', 'value' => $records->total(), 'format' => 'number', 'caption' => 'Across all tenants', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Open Tickets', 'value' => $openCount, 'format' => 'number', 'caption' => 'Awaiting resolution', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Unread Replies', 'value' => $unreadCount, 'format' => 'number', 'caption' => 'Tickets with a new tenant reply', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'rows' => collect($records->items())->map(fn (SupportTicket $ticket) => [
                '<div class="entity-title">' . e($ticket->subject) . ($ticket->admin_has_unread ? ' <span class="badge badge-amber">New</span>' : '') . '</div>',
                '<div class="entity-title">' . e($ticket->tenant_id) . '</div>',
                '<span class="badge badge-cyan">' . e($priorityOptions[$ticket->priority] ?? ucfirst($ticket->priority)) . '</span>',
                '<span class="badge ' . match ($ticket->status) { 'resolved', 'closed' => 'badge-green', 'in_progress' => 'badge-amber', default => 'badge-cyan' } . '">' . e($statusOptions[$ticket->status] ?? ucfirst($ticket->status)) . '</span>',
                $ticket->last_reply_at ? e($ticket->last_reply_at->format('M d, Y H:i')) : '—',
                '<a href="' . route('admin.support.show', $ticket->id) . '" class="link-btn">View</a>',
            ])->all(),
            'records' => $records,
            'tableDescription' => $records->total() . ' support tickets matched the current filters.',
            'emptyCopy' => 'No support tickets matched the current search and filter combination.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter']);
        $this->resetPage();
    }
}
