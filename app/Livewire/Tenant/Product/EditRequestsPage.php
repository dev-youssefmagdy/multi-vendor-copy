<?php

namespace App\Livewire\Tenant\Product;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ProductEditRequest;
use Livewire\WithPagination;

class EditRequestsPage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $statusFilter = 'all';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Product Edit Requests',
            'badge' => 'Catalog',
            'description' => 'Track the status of your product name and description change requests.',
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->getTenantKey();

        $requests = tenancy()->central(fn() => ProductEditRequest::forTenant($tenantId)
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20));

        $pendingCount = tenancy()->central(fn() => ProductEditRequest::forTenant($tenantId)->where('status', 'pending')->count());
        $approvedCount = tenancy()->central(fn() => ProductEditRequest::forTenant($tenantId)->where('status', 'approved')->count());
        $rejectedCount = tenancy()->central(fn() => ProductEditRequest::forTenant($tenantId)->where('status', 'rejected')->count());

        return array_merge(parent::pageData(), [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.product.edit-requests-page';
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}
