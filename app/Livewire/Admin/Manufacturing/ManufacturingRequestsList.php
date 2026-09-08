<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Enums\ManufacturingRequestStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\ManufacturingRequest;
use App\Models\Tenant;
use App\Services\TenantNotificationService;
use Livewire\WithPagination;

class ManufacturingRequestsList extends ListPage
{
    use WithPagination, InteractsWithAdminUi;

    public string $search = '';
    public string $statusFilter = '';
    public string $tenantFilter = '';

    public bool $showModal = false;
    public ?int $selectedId = null;
    public string $newStatus = '';
    public string $adminNotes = '';
    public string $selectedProductName = '';
    public string $selectedTenantName = '';
    public string $selectedCurrentStatus = '';
    public string $selectedCurrentStatusClass = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Manufacturing Requests',
            'badge' => 'All Tenants',
            'description' => 'Review and manage tenant manufacturing requests with status tracking and notifications.',
            'actionLabel' => null,
            'filtersDescription' => 'Filter requests by tenant, product name, or status.',
            'tableTitle' => 'Manufacturing Requests',
            'headers' => ['Request', 'Tenant', 'Product', 'Qty', 'Status', 'Submitted', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $requests = ManufacturingRequest::query()
            ->with(['tenant'])
            ->when(filled($this->search), fn($q) => $q->where('product_name', 'like', "%{$this->search}%"))
            ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->tenantFilter), fn($q) => $q->where('tenant_id', $this->tenantFilter))
            ->latest()
            ->paginate(15);

        $tenants = Tenant::query()->orderBy('data->name')->get(['id', 'data->name as name']);

        $stats = [
            'total' => ManufacturingRequest::count(),
            'pending' => ManufacturingRequest::where('status', ManufacturingRequestStatus::Pending->value)->count(),
            'in_production' => ManufacturingRequest::where('status', ManufacturingRequestStatus::InProduction->value)->count(),
            'completed' => ManufacturingRequest::where('status', ManufacturingRequestStatus::Completed->value)->count(),
        ];

        return array_merge(parent::pageData(), [
            'requests' => $requests,
            'tenants' => $tenants,
            'stats' => $stats,
            'statusOptions' => ManufacturingRequestStatus::cases(),
            'canManage' => $this->hasPermission('manufacturing.manage'),
            'showModal' => $this->showModal,
            'selectedId' => $this->selectedId,
            'newStatus' => $this->newStatus,
            'adminNotes' => $this->adminNotes,
            'selectedProductName' => $this->selectedProductName,
            'selectedTenantName' => $this->selectedTenantName,
            'selectedCurrentStatus' => $this->selectedCurrentStatus,
            'selectedCurrentStatusClass' => $this->selectedCurrentStatusClass,
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.admin.manufacturing.manufacturing-requests-list';
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
        $this->authorizePermission('manufacturing.manage');
        $request = ManufacturingRequest::with('tenant')->findOrFail($id);
        $this->selectedId = $id;
        $this->newStatus = $request->status->value;
        $this->adminNotes = (string) $request->admin_notes;
        $this->selectedProductName = $request->product_name;
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
        $this->selectedProductName = '';
        $this->selectedTenantName = '';
        $this->selectedCurrentStatus = '';
        $this->selectedCurrentStatusClass = '';
    }

    public function updateStatus(TenantNotificationService $notifier): void
    {
        $this->authorizePermission('manufacturing.manage');

        $this->validate([
            'newStatus' => 'required|in:' . implode(',', array_column(ManufacturingRequestStatus::cases(), 'value')),
            'adminNotes' => 'nullable|string|max:2000',
        ]);

        $request = ManufacturingRequest::findOrFail($this->selectedId);
        $oldStatus = $request->status;
        $newStatus = ManufacturingRequestStatus::from($this->newStatus);

        $request->update([
            'status' => $newStatus->value,
            'admin_notes' => $this->adminNotes ?: null,
        ]);

        if ($oldStatus !== $newStatus) {
            $notifier->notifyById(
                $request->tenant_id,
                'manufacturing_status',
                'Manufacturing Request Updated',
                'Your manufacturing request for "' . $request->product_name . '" is now: ' . $newStatus->label(),
                [
                    'request_id' => $request->id,
                    'product_name' => $request->product_name,
                    'status' => $newStatus->value,
                    'admin_notes' => $request->admin_notes,
                ]
            );
        }

        $this->closeModal();
        $this->toast('Status updated and tenant notified.');
    }
}
