<?php

namespace App\Livewire\Tenant\BrandRequest;

use App\Enums\BrandRequestStatus;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\BrandRequest;
use Livewire\WithPagination;

class BrandRequestsList extends TenantPage
{
    use InteractsWithTenantUi, WithPagination;

    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'pageTitle' => 'Brand Requests',
            'badge' => 'Brands',
            'pageDescription' => 'Track the status of your brand requests to the admin team.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.brand-request.list';
    }

    protected function pageData(): array
    {
        $tenantId = tenant('id');

        $records = BrandRequest::where('tenant_id', $tenantId)
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => BrandRequest::where('tenant_id', $tenantId)->count(),
            'pending' => BrandRequest::where('tenant_id', $tenantId)->where('status', BrandRequestStatus::Pending->value)->count(),
            'approved' => BrandRequest::where('tenant_id', $tenantId)->where('status', BrandRequestStatus::Approved->value)->count(),
        ];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'stats' => $stats,
            'statusOptions' => BrandRequestStatus::cases(),
        ]);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}
