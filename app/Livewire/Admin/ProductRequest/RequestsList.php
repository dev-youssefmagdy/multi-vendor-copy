<?php

namespace App\Livewire\Admin\ProductRequest;

use App\Enums\ProductRequestStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Models\ProductRequest;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class RequestsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorizeAnyPermission(['catalog.product-requests.view', 'catalog.product-requests.manage']);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Product Requests',
            'badge' => 'Catalog',
            'description' => 'Product addition requests submitted by tenants.',
            'tableTitle' => 'Requests',
            // PANELS-UPDATES-PRODUCT-REQUESTS: readable store name instead of the tenant UUID.
            'headers' => ['Request', 'Store', 'Status', 'Priority', 'Last Update', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        // PANELS-UPDATES-PRODUCT-REQUESTS: search by code (PR-00042), title, description, tenant id or STORE NAME;
        // archived requests are hidden unless the Archived filter is selected.
        $query = ProductRequest::query()->with('tenant')->search($this->search);

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        } else {
            $query->where('status', '!=', ProductRequestStatus::Archived->value);
        }

        $records = $query->orderByDesc('admin_has_unread')->orderByDesc('last_reply_at')->paginate(20);

        $statusOptions = ProductRequestStatus::options();

        $stats = [
            'total' => ProductRequest::query()->count(),
            'unread' => ProductRequest::query()->where('admin_has_unread', true)->count(),
            'pending' => ProductRequest::query()->where('status', ProductRequestStatus::Pending->value)->count(),
        ];

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Code, title or store name'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All Statuses'] + $statusOptions],
            ],
            'statisticsGridClass' => 'g-stats3',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'All product requests', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Unread', 'value' => $stats['unread'], 'format' => 'number', 'caption' => 'Awaiting admin response', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'format' => 'number', 'caption' => 'Not yet reviewed', 'dot' => 'dot-violet', 'glow' => 'card-glow-violet'],
            ]),
            'rows' => collect($records->items())->map(fn (ProductRequest $r) => [
                '<div class="entity-title">'
                    . e($r->title)
                    . ($r->admin_has_unread ? ' <span class="badge badge-amber">New</span>' : '')
                    . '</div><div class="entity-subtitle">' . e($r->code) . ' &middot; ' . e(Str::limit($r->description, 60)) . '</div>',
                '<div class="entity-title">' . e($r->storeName()) . '</div><div class="entity-subtitle">' . e(Str::limit($r->tenant_id, 13, '…')) . '</div>',
                '<span class="badge ' . $r->status->badgeClass() . '">' . e($r->status->label()) . '</span>',
                '<span class="badge badge-secondary">' . e(ucfirst($r->priority)) . '</span>',
                $r->last_reply_at ? e($r->last_reply_at->diffForHumans()) : '—',
                '<a href="' . route('admin.product-requests.show', $r->id) . '" class="link-btn">View</a>'
                    . (in_array($r->status, [ProductRequestStatus::Completed, ProductRequestStatus::Rejected], true)
                        ? ' <button type="button" class="link-btn" wire:click="archive(' . $r->id . ')" wire:confirm="Archive this finished request?">Archive</button>'
                        : ''),
            ])->all(),
            'records' => $records,
            'tableDescription' => $records->total() . ' product requests matched the current filters.',
            'emptyCopy' => 'No product requests matched the current search and filter combination.',
        ]);
    }

    /** PANELS-UPDATES-PRODUCT-REQUESTS: move a finished (completed / rejected) request out of the active list. */
    public function archive(int $id): void
    {
        $this->authorizePermission('catalog.product-requests.manage');

        $request = ProductRequest::find($id);

        if ($request && in_array($request->status, [ProductRequestStatus::Completed, ProductRequestStatus::Rejected], true)) {
            $request->update(['status' => ProductRequestStatus::Archived->value, 'admin_has_unread' => false]);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}
