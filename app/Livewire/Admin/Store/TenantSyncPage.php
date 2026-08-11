<?php

namespace App\Livewire\Admin\Store;

use App\Jobs\SyncTenantSectionsJob;
use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Tenant;

class TenantSyncPage extends AdminPage
{
    use InteractsWithAdminUi;

    public array $selectedSections = [];
    public array $selectedTenants = [];
    public bool $allTenants = false;
    public bool $syncing = false;
    public ?string $successMessage = null;
    public string $tenantSearch = '';

    protected array $availableSections = [
        'languages' => 'Languages',
        'currencies' => 'Currencies',
        'settings' => 'Settings',
        'payment-gateways' => 'Payment Gateways',
        'themes' => 'Themes',
        'email-templates' => 'Email Templates',
        'pages' => 'Pages',
        'categories' => 'Categories',
        'products' => 'Products',
        'badges.new-in' => 'New-In Badge',
        'badges.best-selling' => 'Best-Selling Badge',
        'flash-sales' => 'Flash Sales',
        'coupons' => 'Coupons',
    ];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Tenant Sync',
            'badge' => 'Store',
            'description' => 'Manually trigger a sync of specific sections to one or more tenant stores.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.store.tenant-sync-page';
    }

    protected function pageData(): array
    {
        $this->authorizePermission('store.sync.manage');

        $tenants = Tenant::query()->orderBy('data->name')->get(['id', 'data->name as name', 'data->slug as slug']);

        return array_merge($this->pageMeta(), [
            'availableSections' => $this->availableSections,
            'tenants'           => $tenants,
            'tenantSearch'      => $this->tenantSearch,
        ]);
    }

    public function sync(): void
    {
        $this->authorizePermission('store.sync.manage');

        $this->validate([
            'selectedSections' => ['required', 'array', 'min:1'],
            'selectedSections.*' => ['string', 'in:' . implode(',', array_keys($this->availableSections))],
            'allTenants' => ['boolean'],
            'selectedTenants' => ['array'],
        ]);

        if (!$this->allTenants && empty($this->selectedTenants)) {
            $this->addError('selectedTenants', 'Select at least one tenant or choose all tenants.');
            return;
        }

        $sections = $this->resolveSections($this->selectedSections);

        $tenants = $this->allTenants
            ? Tenant::query()->orderBy('id')->get()
            : Tenant::query()->whereIn('id', $this->selectedTenants)->get();

        foreach ($tenants as $tenant) {
            SyncTenantSectionsJob::dispatch($tenant->id, $sections);
        }

        $this->successMessage = sprintf(
            'Sync queued for %d tenant(s) — sections: %s.',
            $tenants->count(),
            implode(', ', array_map(fn($s) => $this->availableSections[$s] ?? $s, $this->selectedSections)),
        );

        $this->selectedSections = [];
        $this->selectedTenants = [];
        $this->allTenants = false;
    }

    /**
     * Map badge sub-keys to the actual section key used by TenantCatalogSyncService.
     */
    protected function resolveSections(array $keys): array
    {
        $sections = [];
        foreach ($keys as $key) {
            $sections[] = str_starts_with($key, 'badges.') ? 'badges' : $key;
        }
        return array_values(array_unique($sections));
    }
}
