<?php

namespace App\Livewire\Admin\Customer;

use App\Livewire\Admin\Base\ListPage;
use App\Repositories\Admin\CustomerRepository;
use Livewire\WithPagination;

class CentralCustomersList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $newOnlyFilter = false;

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Customers',
            'badge' => 'All Tenants',
            'description' => 'Browse customers registered across every tenant storefront on the marketplace.',
            'tableTitle' => 'Customer List',
            'headers' => ['Customer', 'Store', 'Contact', 'Orders', 'Registered', 'Status'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(CustomerRepository::class);
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'new_only' => $this->newOnlyFilter,
        ];
        $records = $repository->paginate($filters);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name, email, phone, or store'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive']],
                ['label' => 'New Customers (Last 7 Days)', 'model' => 'newOnlyFilter', 'type' => 'checkbox', 'toggleLabel' => 'New Customers (Last 7 Days)'],
            ],
            'filtersNote' => 'Customers are aggregated live across every tenant database.',
            'statisticsGridClass' => 'g-stats3',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Customers', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Registered across all tenants.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Currently allowed to order.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'New (Last 7 Days)', 'value' => $stats['new'], 'format' => 'number', 'caption' => 'Registered within the last week.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'rows' => collect($records->items())->map(fn(object $customer) => [
                '<div class="entity-title">' . e($customer->full_name) . '</div>' . ($repository->isNew($customer) ? '<span class="badge badge-green customer-new-badge">New</span>' : ''),
                e($customer->store_name ?? 'Unknown store'),
                '<div class="entity-title">' . e($customer->email ?: '-') . '</div><div class="entity-subtitle">' . e($customer->phone ?: 'No phone') . '</div>',
                e((string) $customer->orders_count),
                e(optional($customer->created_at)->format('M d, Y') ?: 'N/A'),
                '<span class="badge ' . ($customer->active ? 'badge-green' : 'badge-amber') . '">' . e($customer->active ? 'Active' : 'Inactive') . '</span>',
            ])->all(),
            'rowClasses' => collect($records->items())->map(fn(object $customer) => $repository->isNew($customer) ? 'row-new-customer' : '')->all(),
            'tableDescription' => $records->total() . ' customers matched the current filters.',
            'emptyCopy' => 'No customers matched the current search and filters.',
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

    public function updatedNewOnlyFilter(): void
    {
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'customers-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Store', 'Name', 'Email', 'Phone', 'Orders', 'Status', 'Registered At'];
    }

    protected function exportRows(): array
    {
        $repository = app(CustomerRepository::class);

        return $repository->export([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'new_only' => $this->newOnlyFilter,
        ])->map(fn(object $customer) => [
            $customer->store_name ?? 'Unknown store',
            $customer->full_name,
            $customer->email,
            $customer->phone,
            $customer->orders_count,
            $customer->active ? 'Active' : 'Inactive',
            $customer->created_at?->format('Y-m-d H:i') ?? '',
        ])->all();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'newOnlyFilter']);
        $this->resetPage();
    }
}
