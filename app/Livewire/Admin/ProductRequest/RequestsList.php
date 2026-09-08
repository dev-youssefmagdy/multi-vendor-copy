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
            'headers' => ['Request', 'Tenant', 'Status', 'Priority', 'Last Update', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $query = ProductRequest::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('tenant_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
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
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Title or tenant ID'],
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
                    . '</div><div class="entity-subtitle">#' . $r->id . ' &middot; ' . e(Str::limit($r->description, 60)) . '</div>',
                e($r->tenant_id),
                '<span class="badge ' . $r->status->badgeClass() . '">' . e($r->status->label()) . '</span>',
                '<span class="badge badge-secondary">' . e(ucfirst($r->priority)) . '</span>',
                $r->last_reply_at ? e($r->last_reply_at->diffForHumans()) : '—',
                '<a href="' . route('admin.product-requests.show', $r->id) . '" class="link-btn">View</a>',
            ])->all(),
            'records' => $records,
            'tableDescription' => $records->total() . ' product requests matched the current filters.',
            'emptyCopy' => 'No product requests matched the current search and filter combination.',
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}
