<?php

namespace App\Livewire\Tenant\ProductRequest;

use App\Enums\ProductRequestStatus;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ProductRequest;
use Livewire\WithPagination;

class RequestsList extends TenantPage
{
    use InteractsWithTenantUi, WithPagination;

    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Product Requests',
            'badge'       => 'Catalog',
            'description' => 'Track the status of your product requests to the Neozena team.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.product-request.list';
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->getTenantKey();

        $records = tenancy()->central(function () use ($tenantId) {
            return ProductRequest::forTenant($tenantId)
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('last_reply_at')
                ->paginate(15);
        });

        $stats = tenancy()->central(fn() => [
            'total'  => ProductRequest::forTenant($tenantId)->count(),
            'open'   => ProductRequest::forTenant($tenantId)->open()->count(),
            'unread' => ProductRequest::forTenant($tenantId)->where('tenant_has_unread', true)->count(),
        ]);

        return array_merge(parent::pageData(), [
            'records'       => $records,
            'stats'         => $stats,
            'statusOptions' => ProductRequestStatus::options(),
        ]);
    }
}
