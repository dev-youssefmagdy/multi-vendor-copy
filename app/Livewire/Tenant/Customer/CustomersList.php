<?php

namespace App\Livewire\Tenant\Customer;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Customer;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\WithPagination;

class CustomersList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => __('Customers'),
            'badge' => 'CRM',
            'description' => __('Manage tenant customers with live order counts, lifetime value, and repeat-buyer context alongside the edit workflow.'),
            'actionLabel' => __('Add Customer'),
            'tableTitle' => __('Customer List'),
            'headers' => ['ID', __('Customer'), __('Contact'), __('Orders'), __('Lifetime Value'), __('Last Order'), __('Status'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateCustomers(['search' => $this->search, 'status' => $this->statusFilter]);
        $customers = EloquentCollection::make($records->items())->load(['orders.items']);
        $stats = $repository->customerStats();

        return array_merge(parent::pageData(), [
            'actionUrl' => route('tenant.customers.create'),
            'records' => $records,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Name, email, or phone')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => __('All'), 'active' => __('Active'), 'inactive' => __('Inactive')]],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => __('Customers'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('Tenant customer records.'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active'), 'value' => $stats['active'], 'format' => 'number', 'caption' => __('Customers currently allowed to order.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Buyers'), 'value' => $stats['buyers'], 'format' => 'number', 'caption' => __('Profiles that have already converted into orders.'), 'dot' => 'dot-violet'],
                ['label' => __('Avg Lifetime'), 'value' => $stats['avg_lifetime'], 'format' => 'currency', 'caption' => __('Average customer lifetime spend for ordering customers.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'rows' => $customers->map(fn(Customer $customer) => [
                '<span class="entity-subtitle" style="font-family:monospace;">#' . $customer->id . '</span>',
                '<div class="entity-title">' . e($customer->full_name) . '</div><div class="entity-subtitle">' . e($customer->email) . '</div>',
                '<div class="entity-title">' . e($customer->phone ?: '-') . '</div><div class="entity-subtitle">' . e($customer->address ?: __('No saved address')) . '</div>',
                '<div class="entity-title">' . e((string) $customer->orders_count) . '</div><div class="entity-subtitle">' . e(__(':count paid', ['count' => $customer->paid_orders_count])) . '</div>',
                '$' . e(number_format((float) $customer->orders->sum(fn($order) => $order->grand_total), 2)) . '<div class="entity-subtitle">' . e(__('Avg $:avg', ['avg' => number_format($customer->orders_count > 0 ? ((float) $customer->orders->sum(fn($order) => $order->grand_total) / $customer->orders_count) : 0, 2)])) . '</div>',
                e(optional($customer->orders->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: 'N/A'),
                '<span class="badge ' . ($customer->active ? 'badge-green' : 'badge-amber') . '">' . e($customer->active ? __('Active') : __('Inactive')) . '</span>',
                '<div class="flex gap-2"><a href="' . route('tenant.customers.show', $customer->id) . '" class="btn btn-primary btn-sm">' . e(__('Edit')) . '</a><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $customer->id . ')">' . e(__('Delete')) . '</button></div>',
            ])->all(),
            'tableDescription' => $records->total() . ' customers matched the current CRM filters.',
        ]);
    }

    public function confirmDelete(int $customerId): void
    {
        $this->confirmAction('deleteCustomer', [$customerId], [
            'title' => __('Delete customer?'),
            'text' => __('This customer record will be removed from the tenant workspace.'),
            'confirmButtonText' => __('Delete customer'),
        ]);
    }

    public function deleteCustomer(int $customerId, TenantPanelService $service): void
    {
        $service->deleteModel(Customer::query()->findOrFail($customerId));
        $this->toast(__('Customer deleted successfully.'));
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

    protected function exportFileName(): string
    {
        return 'customers-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['ID', __('Name'), __('Email'), __('Phone'), __('Address'), __('Orders'), __('Paid Orders'), __('Status'), __('Registered At')];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantPanelRepository::class);
        return $repository->exportCustomers(['search' => $this->search, 'status' => $this->statusFilter])
            ->map(fn(Customer $customer) => [
                $customer->id,
                $customer->full_name,
                $customer->email,
                $customer->phone ?: '',
                $customer->address ?: '',
                $customer->orders_count,
                $customer->paid_orders_count,
                $customer->active ? __('Active') : __('Inactive'),
                $customer->created_at?->format('Y-m-d') ?? '',
            ])->all();
    }
}
