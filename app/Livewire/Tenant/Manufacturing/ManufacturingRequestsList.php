<?php

namespace App\Livewire\Tenant\Manufacturing;

use App\Enums\ManufacturingRequestStatus;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ManufacturingRequest;
use Livewire\WithPagination;

class ManufacturingRequestsList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Manufacturing Requests',
            'badge' => 'My Requests',
            'description' => 'Track your manufacturing requests and monitor their status updates from the admin team.',
            'actionMethod' => 'redirectToCreate',
            'actionLabel' => 'New Request',
            'filtersDescription' => 'Filter your requests by product name or status.',
            'tableTitle' => 'Your Requests',
            'headers' => ['Product', 'Qty', 'Status', 'Admin Notes', 'Submitted', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant('id');

        $records = ManufacturingRequest::query()
            ->where('tenant_id', $tenantId)
            ->when(filled($this->search), fn($q) => $q->where('product_name', 'like', "%{$this->search}%"))
            ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => ManufacturingRequest::where('tenant_id', $tenantId)->count(),
            'pending' => ManufacturingRequest::where('tenant_id', $tenantId)->where('status', ManufacturingRequestStatus::Pending->value)->count(),
            'completed' => ManufacturingRequest::where('tenant_id', $tenantId)->where('status', ManufacturingRequestStatus::Completed->value)->count(),
        ];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'stats' => $stats,
            'statusOptions' => ManufacturingRequestStatus::cases(),
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Search by product name...'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All Statuses'] + collect(ManufacturingRequestStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])->all()],
            ],
            'statisticsGridClass' => 'g-stats3',
            'statistics' => [
                ['label' => 'Total Requests', 'value' => $stats['total'], 'caption' => 'All your manufacturing requests.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'caption' => 'Awaiting admin review.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Completed', 'value' => $stats['completed'], 'caption' => 'Successfully fulfilled.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'rows' => collect($records->items())->map(fn(ManufacturingRequest $req) => [
                '<div class="entity-title">' . e($req->product_name) . '</div>'
                . ($req->description ? '<div class="entity-subtitle" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' . e($req->description) . '</div>' : ''),
                e($req->quantity),
                '<span class="badge ' . e($req->status->badgeClass()) . '">' . e($req->status->label()) . '</span>',
                $req->admin_notes ? '<div class="entity-subtitle" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' . e($req->admin_notes) . '</div>' : '<span class="entity-subtitle">—</span>',
                e($req->created_at?->format('M d, Y')),
                ($req->status === ManufacturingRequestStatus::Pending
                    ? '<a href="' . route('tenant.manufacturing.show', $req->id) . '" class="btn btn-secondary btn-sm" title="View full details"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-1px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg> View</a>'
                    . ' <button type="button" class="btn btn-danger btn-sm" wire:click="cancelRequest(' . $req->id . ')" wire:confirm="Cancel this request?">Cancel</button>'
                    : '<a href="' . route('tenant.manufacturing.show', $req->id) . '" class="btn btn-secondary btn-sm" title="View full details"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-1px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg> View</a>'),
            ])->all(),
            'emptyTitle' => 'No manufacturing requests',
            'emptyCopy' => 'Submit your first manufacturing request using the button above.',
            'tableDescription' => $records->total() . ' requests found.',
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.pages.list-page';
    }

    public function redirectToCreate(): void
    {
        $this->redirect(route('tenant.manufacturing.create'));
    }

    public function cancelRequest(int $id): void
    {
        $tenantId = tenant('id');
        $request = ManufacturingRequest::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('status', ManufacturingRequestStatus::Pending->value)
            ->firstOrFail();

        $request->update(['status' => ManufacturingRequestStatus::Cancelled->value]);
        $this->toast('Request cancelled.');
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

    protected function exportFileName(): string
    {
        return 'manufacturing-requests-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['ID', 'Product Name', 'Quantity', 'Description', 'Status', 'Admin Notes', 'Submitted At'];
    }

    protected function exportRows(): array
    {
        $tenantId = tenant('id');
        return ManufacturingRequest::query()
            ->where('tenant_id', $tenantId)
            ->when(filled($this->search), fn($q) => $q->where('product_name', 'like', "%{$this->search}%"))
            ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->get()
            ->map(fn(ManufacturingRequest $req) => [
                $req->id,
                $req->product_name,
                $req->quantity,
                $req->description ?? '',
                $req->status->label(),
                $req->admin_notes ?? '',
                $req->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }
}
