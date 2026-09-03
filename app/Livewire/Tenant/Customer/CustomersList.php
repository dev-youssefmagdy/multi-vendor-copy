<?php

namespace App\Livewire\Tenant\Customer;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\City;
use App\Models\Country;
use App\Models\Tenant\Customer;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class CustomersList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showFormModal = false;
    public ?int $customerId = null;
    public string $fullName = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $countryId = '';
    public string $cityId = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public bool $active = true;

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Customers',
            'badge' => 'CRM',
            'description' => 'Manage tenant customers with live order counts, lifetime value, and repeat-buyer context alongside the edit workflow.',
            'actionLabel' => 'Add Customer',
            'tableTitle' => 'Customer List',
            'headers' => ['ID', 'Customer', 'Contact', 'Orders', 'Lifetime Value', 'Last Order', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateCustomers(['search' => $this->search, 'status' => $this->statusFilter]);
        $customers = EloquentCollection::make($records->items())->load(['orders.items']);
        $stats = $repository->customerStats();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name, email, or phone'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Customers', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Tenant customer records.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Customers currently allowed to order.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Buyers', 'value' => $stats['buyers'], 'format' => 'number', 'caption' => 'Profiles that have already converted into orders.', 'dot' => 'dot-violet'],
                ['label' => 'Avg Lifetime', 'value' => $stats['avg_lifetime'], 'format' => 'currency', 'caption' => 'Average customer lifetime spend for ordering customers.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'rows' => $customers->map(fn(Customer $customer) => [
                '<span class="entity-subtitle" style="font-family:monospace;">#' . $customer->id . '</span>',
                '<div class="entity-title">' . e($customer->full_name) . '</div><div class="entity-subtitle">' . e($customer->email) . '</div>',
                '<div class="entity-title">' . e($customer->phone ?: '-') . '</div><div class="entity-subtitle">' . e($customer->address ?: 'No saved address') . '</div>',
                '<div class="entity-title">' . e((string) $customer->orders_count) . '</div><div class="entity-subtitle">' . e((string) $customer->paid_orders_count) . ' paid</div>',
                '$' . e(number_format((float) $customer->orders->sum(fn($order) => $order->grand_total), 2)) . '<div class="entity-subtitle">Avg $' . e(number_format($customer->orders_count > 0 ? ((float) $customer->orders->sum(fn($order) => $order->grand_total) / $customer->orders_count) : 0, 2)) . '</div>',
                e(optional($customer->orders->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: 'N/A'),
                '<span class="badge ' . ($customer->active ? 'badge-green' : 'badge-amber') . '">' . e($customer->active ? 'Active' : 'Inactive') . '</span>',
                '<div class="flex gap-2"><a href="' . route('tenant.customers.show', $customer->id) . '" class="btn btn-primary btn-sm">View</a><button type="button" class="btn btn-secondary btn-sm" wire:click="editCustomer(' . $customer->id . ')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $customer->id . ')">Delete</button></div>',
            ])->all(),
            'tableDescription' => $records->total() . ' customers matched the current CRM filters.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->customerId ? 'Edit Customer' : 'Add Customer',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->customerId ? 'Update Customer' : 'Create Customer',
            'modalContentView' => 'livewire.tenant.customer.partials.customer-form',
            'modalContentData' => [
                'countries' => Country::with('translations.language')->orderBy('name')->get(['id', 'name'])->pluck('name', 'id'),
                'cities'    => $this->countryId
                    ? City::where('country_id', $this->countryId)->orderBy('name')->pluck('name', 'id')
                    : collect(),
            ],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editCustomer(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        $this->customerId = $customer->id;
        $this->fullName = $customer->full_name;
        $this->email = $customer->email;
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->countryId = (string) ($customer->country_id ?? '');
        $this->cityId = (string) ($customer->city_id ?? '');
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->active = $customer->active;
        $this->showFormModal = true;
    }

    public function save(TenantPanelService $service): void
    {
        $customerId = $this->customerId;
        $validated = $this->validate([
            'fullName'            => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone'               => ['nullable', 'string', 'max:50'],
            'address'             => ['nullable', 'string', 'max:255'],
            'countryId'           => ['nullable', 'integer'],
            'cityId'              => ['nullable', 'integer'],
            'password'            => [$customerId ? 'nullable' : 'required', 'string', 'min:6', 'same:passwordConfirmation'],
            'passwordConfirmation'=> ['nullable', 'string'],
            'active'              => ['boolean'],
        ]);

        $service->saveCustomer([
            'full_name'  => $validated['fullName'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? '',
            'address'    => $validated['address'] ?? '',
            'country_id' => filled($validated['countryId']) ? (int) $validated['countryId'] : null,
            'city_id'    => filled($validated['cityId']) ? (int) $validated['cityId'] : null,
            'password'   => $validated['password'] ?? null,
            'active'     => $validated['active'],
        ], $customerId ? Customer::query()->findOrFail($customerId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($customerId ? 'Customer updated successfully.' : 'Customer created successfully.');
        $this->resetPage();
    }

    public function confirmDelete(int $customerId): void
    {
        $this->confirmAction('deleteCustomer', [$customerId], [
            'title' => 'Delete customer?',
            'text' => 'This customer record will be removed from the tenant workspace.',
            'confirmButtonText' => 'Delete customer',
        ]);
    }

    public function deleteCustomer(int $customerId, TenantPanelService $service): void
    {
        $service->deleteModel(Customer::query()->findOrFail($customerId));
        $this->toast('Customer deleted successfully.');
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    protected function resetForm(): void
    {
        $this->reset(['customerId', 'fullName', 'email', 'phone', 'address', 'countryId', 'cityId', 'password', 'passwordConfirmation']);
        $this->active = true;
        $this->resetErrorBag();
    }

    public function updatedCountryId(): void
    {
        $this->cityId = '';
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
        return ['ID', 'Name', 'Email', 'Phone', 'Address', 'Orders', 'Paid Orders', 'Status', 'Registered At'];
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
                $customer->active ? 'Active' : 'Inactive',
                $customer->created_at?->format('Y-m-d') ?? '',
            ])->all();
    }
}
