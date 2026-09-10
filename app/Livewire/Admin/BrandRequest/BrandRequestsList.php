<?php

namespace App\Livewire\Admin\BrandRequest;

use App\Enums\BrandRequestStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\BrandRequest;
use App\Models\Tenant;
use App\Services\TenantNotificationService;
use Livewire\WithPagination;

class BrandRequestsList extends ListPage
{
    use WithPagination, InteractsWithAdminUi;

    public string $search = '';
    public string $statusFilter = '';
    public string $tenantFilter = '';

    public bool $showModal = false;
    public ?int $selectedId = null;
    public string $newStatus = '';
    public string $adminNotes = '';
    public string $selectedTitle = '';
    public string $selectedTenantName = '';
    public string $selectedCurrentStatus = '';
    public string $selectedCurrentStatusClass = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Brand Requests',
            'badge' => 'All Tenants',
            'description' => 'Review and manage tenant brand requests with status tracking and notifications.',
            'actionLabel' => null,
            'filtersDescription' => 'Filter requests by tenant, title, or status.',
            'tableTitle' => 'Brand Requests',
            'headers' => ['Request', 'Tenant', 'Status', 'Submitted', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $requests = BrandRequest::query()
            ->with(['tenant'])
            ->when(filled($this->search), fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->tenantFilter), fn($q) => $q->where('tenant_id', $this->tenantFilter))
            ->latest()
            ->paginate(15);

        $tenants = Tenant::query()->orderBy('data->name')->get(['id', 'data->name as name']);

        $stats = [
            'total' => BrandRequest::count(),
            'pending' => BrandRequest::where('status', BrandRequestStatus::Pending->value)->count(),
            'approved' => BrandRequest::where('status', BrandRequestStatus::Approved->value)->count(),
            'completed' => BrandRequest::where('status', BrandRequestStatus::Completed->value)->count(),
        ];

        return array_merge(parent::pageData(), [
            'requests' => $requests,
            'tenants' => $tenants,
            'stats' => $stats,
            'statusOptions' => BrandRequestStatus::cases(),
            'canManage' => $this->hasPermission('brand-requests.manage'),
            'showModal' => $this->showModal,
            'selectedId' => $this->selectedId,
            'newStatus' => $this->newStatus,
            'adminNotes' => $this->adminNotes,
            'selectedTitle' => $this->selectedTitle,
            'selectedTenantName' => $this->selectedTenantName,
            'selectedCurrentStatus' => $this->selectedCurrentStatus,
            'selectedCurrentStatusClass' => $this->selectedCurrentStatusClass,
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.admin.brand-request.brand-requests-list';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedTenantFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'tenantFilter']);
        $this->resetPage();
    }

    public function openStatusModal(int $id): void
    {
        $this->authorizePermission('brand-requests.manage');
        $request = BrandRequest::with('tenant')->findOrFail($id);
        $this->selectedId = $id;
        $this->newStatus = $request->status->value;
        $this->adminNotes = (string) $request->admin_notes;
        $this->selectedTitle = $request->title;
        $this->selectedTenantName = $request->tenant?->name ?? $request->tenant_id;
        $this->selectedCurrentStatus = $request->status->label();
        $this->selectedCurrentStatusClass = $request->status->badgeClass();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedId = null;
        $this->newStatus = '';
        $this->adminNotes = '';
        $this->selectedTitle = '';
        $this->selectedTenantName = '';
        $this->selectedCurrentStatus = '';
        $this->selectedCurrentStatusClass = '';
    }

    public function updateStatus(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('brand-requests.manage');

        $this->validate([
            'newStatus' => 'required|in:' . implode(',', array_column(BrandRequestStatus::cases(), 'value')),
            'adminNotes' => 'nullable|string|max:2000',
        ]);

        $request = BrandRequest::findOrFail($this->selectedId);
        $oldStatus = $request->status;
        $newStatus = BrandRequestStatus::from($this->newStatus);

        $request->update([
            'status' => $newStatus->value,
            'admin_notes' => $this->adminNotes ?: null,
        ]);

        if ($oldStatus !== $newStatus) {
            $notifier->notifyById(
                $request->tenant_id,
                'brand_request_status',
                'Brand Request Updated',
                'Your brand request "' . $request->title . '" is now: ' . $newStatus->label(),
                [
                    'request_id' => $request->id,
                    'title' => $request->title,
                    'status' => $newStatus->value,
                    'admin_notes' => $request->admin_notes,
                ]
            );
        }

        $this->closeModal();
        $this->toast('Status updated and tenant notified.');
    }
}
