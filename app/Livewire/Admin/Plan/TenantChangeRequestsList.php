<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\TenantChangeRequestStatus;
use App\Enums\TenantChangeRequestType;
use App\Jobs\ResyncTenantChangeRequestJob;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Category;
use App\Models\Country;
use App\Models\Tenant;
use App\Models\TenantChangeRequest;
use App\Models\TenantCountry;
use App\Services\TenantNotificationService;
use Livewire\Component;
use Livewire\WithPagination;

class TenantChangeRequestsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending'; // all | pending | approved | rejected
    public string $typeFilter = '';

    // Reject modal state
    public ?int $rejectingId = null;
    public string $adminNote = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $this->authorizePermission('plans.tenant-change-requests.manage');

        /** @var TenantChangeRequest $request */
        $request = TenantChangeRequest::query()->findOrFail($id);

        if ($request->status !== TenantChangeRequestStatus::Pending) {
            $this->dispatch('admin-toast', message: 'This request has already been reviewed.', type: 'warning');
            return;
        }

        $tenant = Tenant::query()->find($request->tenant_id);
        if (!$tenant) {
            $this->dispatch('admin-toast', message: 'Tenant not found.', type: 'error');
            return;
        }

        $requestedIds = collect($request->requested_data)->map(fn ($id) => (int) $id)->values()->all();

        if ($request->type === TenantChangeRequestType::Countries) {
            $currentIds = TenantCountry::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->pluck('country_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($requestedIds as $countryId) {
                TenantCountry::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'country_id' => $countryId],
                    ['is_active' => true],
                );
            }

            $removedIds = array_diff($currentIds, $requestedIds);
            if ($removedIds !== []) {
                TenantCountry::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('country_id', $removedIds)
                    ->update(['is_active' => false]);
            }

            $sections = ['products', 'coupons'];
        } else {
            $tenant->fill(['category_ids' => $requestedIds]);
            $tenant->save();

            $sections = ['categories', 'products'];
        }

        $request->update([
            'status' => TenantChangeRequestStatus::Approved,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        ResyncTenantChangeRequestJob::dispatch($tenant->id, $sections);

        app(TenantNotificationService::class)->notify(
            $tenant,
            'tenant_change_request_approved',
            'Change Request Approved',
            "Your {$request->type->label()} change request has been approved. Your store data is being re-synced now.",
        );

        $this->dispatch('admin-toast', message: 'Request approved and tenant data updated.', type: 'success');
    }

    public function openRejectModal(int $id): void
    {
        $this->authorizePermission('plans.tenant-change-requests.manage');
        $this->rejectingId = $id;
        $this->adminNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->adminNote = '';
    }

    public function confirmReject(): void
    {
        $this->authorizePermission('plans.tenant-change-requests.manage');

        if (!$this->rejectingId) {
            return;
        }

        $this->validate(['adminNote' => ['nullable', 'string', 'max:500']]);

        /** @var TenantChangeRequest $request */
        $request = TenantChangeRequest::query()->findOrFail($this->rejectingId);

        if ($request->status !== TenantChangeRequestStatus::Pending) {
            $this->dispatch('admin-toast', message: 'This request has already been reviewed.', type: 'warning');
            $this->cancelReject();
            return;
        }

        $request->update([
            'status' => TenantChangeRequestStatus::Rejected,
            'admin_note' => $this->adminNote ?: null,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $tenant = Tenant::query()->find($request->tenant_id);
        if ($tenant) {
            $noteText = filled($this->adminNote) ? " Reason: {$this->adminNote}" : '';
            app(TenantNotificationService::class)->notify(
                $tenant,
                'tenant_change_request_rejected',
                'Change Request Rejected',
                "Your {$request->type->label()} change request has been rejected.{$noteText}",
            );
        }

        $this->cancelReject();
        $this->dispatch('admin-toast', message: 'Request rejected.', type: 'info');
    }

    public function render()
    {
        $query = TenantChangeRequest::query()->with('tenant');

        if (filled($this->search)) {
            $query->where('tenant_id', 'like', '%' . $this->search . '%');
        }

        if (filled($this->typeFilter)) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $records = $query->latest()->paginate(20);

        $countryNames = Country::query()->pluck('name', 'id');
        $categoryNames = Category::query()
            ->whereNull('parent_id')
            ->with('translations')
            ->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => $c->name]);

        $stats = [
            'total' => TenantChangeRequest::count(),
            'pending' => TenantChangeRequest::where('status', TenantChangeRequestStatus::Pending)->count(),
            'approved' => TenantChangeRequest::where('status', TenantChangeRequestStatus::Approved)->count(),
            'rejected' => TenantChangeRequest::where('status', TenantChangeRequestStatus::Rejected)->count(),
        ];

        return view('livewire.admin.plan.tenant-change-requests-list', [
            'records' => $records,
            'stats' => $stats,
            'canManage' => $this->hasPermission('plans.tenant-change-requests.manage'),
            'countryNames' => $countryNames,
            'categoryNames' => $categoryNames,
        ]);
    }
}
