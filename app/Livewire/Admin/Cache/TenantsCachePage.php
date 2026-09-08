<?php

namespace App\Livewire\Admin\Cache;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Services\CacheAdminService;
use Illuminate\Support\Facades\DB;

class TenantsCachePage extends ContentPage
{
    use InteractsWithAdminUi;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Tenants Cache',
            'badge' => 'Operations',
            'description' => 'Plan central actions for clearing cache across tenant databases and storefront contexts.',
            'bullets' => [
                'Add scoped cache clearing by tenant or by batch.',
                'Show confirmation using Livewire-triggered SweetAlert flows.',
                'Record operation logs for audit and rollback awareness.',
            ],
        ];
    }

    public function requestClearTenants(): void
    {
        $this->authorizePermission('system.cache.manage');
        $this->confirmAction('clearTenants', [], [
            'title' => 'Clear tenant cache?',
            'text' => 'This will clear shared cache state used by tenant storefronts.',
            'confirmButtonText' => 'Clear tenant cache',
        ]);
    }

    public function clearTenants(CacheAdminService $service): void
    {
        $this->authorizePermission('system.cache.manage');
        $service->clearTenants();
        $this->toast('Tenant cache cleared successfully.');
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'cards' => [
                ['label' => 'Tenants', 'value' => number_format(DB::table('tenants')->count()), 'caption' => 'Registered tenant records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Domains', 'value' => number_format(DB::table('domains')->count()), 'caption' => 'Tenant domain mappings', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'actions' => [
                ['label' => 'Clear Tenant Cache', 'method' => 'requestClearTenants', 'variant' => 'btn-primary'],
            ],
        ]);
    }
}
