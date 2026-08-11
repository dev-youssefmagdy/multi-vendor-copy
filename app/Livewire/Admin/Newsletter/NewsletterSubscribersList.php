<?php

namespace App\Livewire\Admin\Newsletter;

use App\Enums\SubscriberStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\NewsletterSubscriber;
use Livewire\WithPagination;

class NewsletterSubscribersList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title'        => 'Newsletter Subscribers',
            'badge'        => 'Marketing',
            'description'  => 'View and manage email subscribers collected from the website newsletter form.',
            'tableTitle'   => 'Subscribers',
            'headers'      => ['Email', 'Source', 'Status', 'Subscribed At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('content.newsletter.manage');

        $query = NewsletterSubscriber::query()
            ->when($this->search, fn($q) => $q->where('email', 'like', '%' . $this->search . '%'))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest('subscribed_at');

        $records = $query->paginate(20);

        $total        = NewsletterSubscriber::count();
        $subscribed   = NewsletterSubscriber::where('status', SubscriberStatus::Subscribed)->count();
        $unsubscribed = NewsletterSubscriber::where('status', SubscriberStatus::Unsubscribed)->count();

        return array_merge(parent::pageData(), [
            'actionUrl'  => null,
            'records'    => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Search by email'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => [
                    ''             => 'All statuses',
                    'subscribed'   => 'Subscribed',
                    'unsubscribed' => 'Unsubscribed',
                ]],
            ],
            'statistics' => [
                ['label' => 'Total', 'value' => number_format($total), 'caption' => 'Emails collected from the website', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Subscribed', 'value' => number_format($subscribed), 'caption' => 'Active subscribers', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Unsubscribed', 'value' => number_format($unsubscribed), 'caption' => 'Opted out', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(NewsletterSubscriber $sub) => [
                '<div class="entity-title">' . e($sub->email) . '</div>',
                '<span class="entity-subtitle">' . e(ucfirst($sub->source ?? 'website')) . '</span>',
                '<span class="badge ' . ($sub->status === SubscriberStatus::Subscribed ? 'badge-green' : 'badge-amber') . '">'
                    . e($sub->status->label()) . '</span>',
                e($sub->subscribed_at?->format('M d, Y') ?? $sub->created_at?->format('M d, Y')),
                $canManage
                    ? '<button type="button" class="btn btn-secondary btn-sm btn-danger" wire:click="confirmDelete(' . $sub->id . ')">Delete</button>'
                    : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' subscriber(s) matched the current filters.',
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizePermission('content.newsletter.manage');
        $this->confirmAction('deleteSubscriber', [$id], [
            'title'             => 'Delete Subscriber?',
            'text'              => 'This email will be permanently removed from the subscribers list.',
            'confirmButtonText' => 'Delete',
        ]);
    }

    public function deleteSubscriber(int $id): void
    {
        $this->authorizePermission('content.newsletter.manage');
        NewsletterSubscriber::query()->findOrFail($id)->delete();
        $this->toast('Subscriber deleted.');
        $this->resetPage();
    }

    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}

