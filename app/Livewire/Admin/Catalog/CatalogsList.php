<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\CatalogRepository;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

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

    public function render(CatalogRepository $catalogs)
    {
        return view('livewire.admin.catalog.catalogs-list', [
            'catalogs' => $catalogs->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
            ]),
            'stats' => $catalogs->stats(),
            'canManageCatalogs' => $this->hasPermission('catalog.catalogs.manage'),
        ]);
    }
}
