<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\TenantStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\HasCsvExport;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Package;
use App\Repositories\TenantRepository;
use Livewire\Component;
use Livewire\WithPagination;

class RegisteredUsersList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;
    use HasCsvExport;
    use InteractsWithAdminUi;

    public string $search = '';
    public string $statusFilter = '';
    public string $packageFilter = '';
    public string $imageFilter = '';

    protected bool $exportable = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPackageFilter(): void
    {
        $this->resetPage();
    }

    public function updatedImageFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'packageFilter', 'imageFilter']);
        $this->resetPage();
    }

    public function confirmDeleteTenant(string $tenantId): void
    {
        $this->authorizePermission('plans.tenants.manage');
        $this->confirmAction('deleteTenant', [$tenantId], [
            'title' => 'Delete Tenant?',
            'text' => 'This will permanently remove the tenant account. This action cannot be undone.',
            'confirmButtonText' => 'Delete',
        ]);
    }

    public function deleteTenant(string $tenantId): void
    {
        $this->authorizePermission('plans.tenants.manage');
        \App\Models\Tenant::query()->findOrFail($tenantId)->delete();
        session()->flash('status', 'Tenant deleted successfully.');
    }

    protected function exportFileName(): string
    {
        return 'tenants-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['ID', 'Name', 'Slug', 'Email', 'Status', 'Package', 'Domain', 'Created At'];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantRepository::class);
        return $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'package_id' => $this->packageFilter,
        ], 100000)->map(fn($tenant) => [
                $tenant->id,
                $tenant->name ?? data_get($tenant->data, 'name') ?? '',
                $tenant->slug ?? data_get($tenant->data, 'slug') ?? '',
                $tenant->email ?? data_get($tenant->data, 'email') ?? '',
                $tenant->status?->value ?? data_get($tenant->data, 'status') ?? '',
                $tenant->package?->name ?? '',
                $tenant->domains->first()?->domain ?? '',
                $tenant->created_at?->format('Y-m-d') ?? '',
            ])->all();
    }

    public function downloadLogo(string $tenantId)
    {
        $tenant = \App\Models\Tenant::query()->findOrFail($tenantId);
        $logoUrl = $tenant->logo;
        abort_unless($logoUrl, 404);

        $contents = \Illuminate\Support\Facades\Http::get($logoUrl)->throw()->body();
        $extension = pathinfo(parse_url($logoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'png';
        $filename = ($tenant->slug ?: $tenant->id) . '-logo.' . $extension;

        return response()->streamDownload(fn() => print($contents), $filename);
    }

    public function render(TenantRepository $tenants)
    {
        return view('livewire.admin.plan.registered-users-list', [
            'tenants' => $tenants->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
                'package_id' => $this->packageFilter,
                'has_image' => $this->imageFilter,
            ]),
            'stats' => $tenants->stats(),
            'statusOptions' => TenantStatus::cases(),
            'packages' => Package::query()->with('translations.language')->get(),
            'canManageTenants' => $this->hasPermission('plans.tenants.manage'),
        ]);
    }
}
