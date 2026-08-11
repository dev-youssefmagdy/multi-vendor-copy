<?php

namespace App\Livewire\Admin\Domain;

use App\Enums\DomainRequestStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\DomainRequest;
use App\Repositories\DomainRequestRepository;
use App\Services\DomainRequestService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class DomainRequestsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showFormModal = false;
    public ?int $domainRequestId = null;
    public ?string $tenantId = null;
    public string $domain = '';
    public string $status = 'pending';
    public string $requestedAt = '';
    public string $verifiedAt = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Domain Requests',
            'badge' => 'All | Pending | Connected | Removed',
            'description' => 'Review custom domain connection requests coming from tenants using full domains instead of subdomains.',
            'actionLabel' => 'Add Request',
            'tableTitle' => 'Custom Domain Queue',
            'headers' => ['Tenant', 'Domain', 'Status', 'Requested At', 'Verified At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('domains.requests.manage');
        $repository = app(DomainRequestRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionMethod' => $canManage ? 'openCreateModal' : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Tenant or domain'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All requests', 'pending' => 'Pending', 'connected' => 'Connected', 'removed' => 'Removed']],
            ],
            'statistics' => [
                ['label' => 'Requests', 'value' => number_format($stats['total']), 'caption' => 'Custom domain connections', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Pending', 'value' => number_format($stats['pending']), 'caption' => 'Waiting for review', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Connected', 'value' => number_format($stats['connected']), 'caption' => 'Already mapped domains', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'rows' => collect($records->items())->map(fn($request) => [
                e($request->tenant?->name ?? 'Unknown tenant'),
                e($request->domain),
                '<span class="badge ' . ($request->status === DomainRequestStatus::Connected ? 'badge-green' : ($request->status === DomainRequestStatus::Removed ? 'badge-red' : 'badge-amber')) . '">' . e($request->status->label()) . '</span>',
                e($request->requested_at?->format('M d, Y H:i') ?? '-'),
                e($request->verified_at?->format('M d, Y H:i') ?? '-'),
                $canManage
                ? '<div class="flex gap-2 flex-wrap"><button type="button" class="btn btn-secondary btn-sm" wire:click="editRequest(' . $request->id . ')">Edit</button>' . ($request->status !== DomainRequestStatus::Connected
                    ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="markConnected(' . $request->id . ')">Connect</button>'
                    : '<button type="button" class="btn btn-secondary btn-sm" wire:click="markPending(' . $request->id . ')">Reset</button>') . ($request->status !== DomainRequestStatus::Removed
                    ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="markRemoved(' . $request->id . ')">Remove</button>'
                    : '') . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $request->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' domain requests matched the current filter set.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->domainRequestId ? 'Review Domain Request' : 'Add Domain Request',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->domainRequestId ? 'Update Request' : 'Create Request',
            'modalFieldGroups' => $canManage ? [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Tenant', 'model' => 'tenantId', 'type' => 'select', 'options' => ['' => 'Unassigned'] + $repository->tenantOptions()],
                        ['label' => 'Status', 'model' => 'status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'connected' => 'Connected', 'removed' => 'Removed']],
                        ['label' => 'Domain', 'model' => 'domain', 'wrapperClass' => 'span-2'],
                        ['label' => 'Requested At', 'model' => 'requestedAt', 'type' => 'datetime-local'],
                        ['label' => 'Verified At', 'model' => 'verifiedAt', 'type' => 'datetime-local'],
                    ],
                ]
            ] : [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('domains.requests.manage');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRequest(int $requestId): void
    {
        $this->authorizePermission('domains.requests.manage');
        $request = DomainRequest::query()->findOrFail($requestId);

        $this->domainRequestId = $request->id;
        $this->tenantId = $request->tenant_id;
        $this->domain = $request->domain;
        $this->status = $request->status->value;
        $this->requestedAt = $request->requested_at?->format('Y-m-d\TH:i') ?? '';
        $this->verifiedAt = $request->verified_at?->format('Y-m-d\TH:i') ?? '';
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function save(DomainRequestService $service): void
    {
        $this->authorizePermission('domains.requests.manage');
        $domainRequestId = $this->domainRequestId;
        $validated = $this->validate([
            'tenantId' => ['nullable', 'string', 'exists:tenants,id'],
            'domain' => ['required', 'string', 'max:255', Rule::unique('domain_requests', 'domain')->ignore($domainRequestId)],
            'status' => ['required', Rule::enum(DomainRequestStatus::class)],
            'requestedAt' => ['nullable', 'date'],
            'verifiedAt' => ['nullable', 'date'],
        ]);

        $service->save([
            'tenant_id' => $validated['tenantId'] ?? null,
            'domain' => $validated['domain'],
            'status' => $validated['status'],
            'requested_at' => $validated['requestedAt'] ?? null,
            'verified_at' => $validated['verifiedAt'] ?? null,
        ], $domainRequestId ? DomainRequest::query()->findOrFail($domainRequestId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($domainRequestId ? 'Domain request updated successfully.' : 'Domain request created successfully.');
        $this->resetPage();
    }

    public function markConnected(int $requestId, DomainRequestService $service): void
    {
        $this->authorizePermission('domains.requests.manage');
        $service->markStatus(DomainRequest::query()->findOrFail($requestId), DomainRequestStatus::Connected);
        $this->toast('Domain request marked as connected.');
    }

    public function markPending(int $requestId, DomainRequestService $service): void
    {
        $this->authorizePermission('domains.requests.manage');
        $service->markStatus(DomainRequest::query()->findOrFail($requestId), DomainRequestStatus::Pending);
        $this->toast('Domain request moved back to pending.');
    }

    public function markRemoved(int $requestId, DomainRequestService $service): void
    {
        $this->authorizePermission('domains.requests.manage');
        $service->markStatus(DomainRequest::query()->findOrFail($requestId), DomainRequestStatus::Removed);
        $this->toast('Domain request marked as removed.');
    }

    public function confirmDelete(int $requestId): void
    {
        $this->authorizePermission('domains.requests.manage');
        $this->confirmAction('deleteRequest', [$requestId], [
            'title' => 'Delete domain request?',
            'text' => 'This removes the review record from the central queue.',
            'confirmButtonText' => 'Delete request',
        ]);
    }

    public function deleteRequest(int $requestId, DomainRequestService $service): void
    {
        $this->authorizePermission('domains.requests.manage');
        $service->delete(DomainRequest::query()->findOrFail($requestId));
        $this->toast('Domain request deleted successfully.');
        $this->resetPage();
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

    protected function resetForm(): void
    {
        $this->reset(['domainRequestId', 'tenantId', 'domain', 'requestedAt', 'verifiedAt']);
        $this->status = DomainRequestStatus::Pending->value;
        $this->resetErrorBag();
    }
}
