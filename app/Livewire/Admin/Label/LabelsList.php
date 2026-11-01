<?php

namespace App\Livewire\Admin\Label;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\ProductLabel;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Support\ProductLabelIcons;
use Livewire\Component;
use Livewire\WithPagination;

class LabelsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $labelId, CentralCatalogTenantSyncService $sync): void
    {
        $this->authorizePermission('catalog.labels.manage');

        $label = ProductLabel::query()->findOrFail($labelId);
        $label->update(['active' => !$label->active]);
        $sync->syncLabels();
    }

    public function deleteLabel(int $labelId, CentralCatalogTenantSyncService $sync): void
    {
        $this->authorizePermission('catalog.labels.manage');

        ProductLabel::query()->findOrFail($labelId)->delete();
        $sync->syncLabels();
        session()->flash('status', 'Label deleted successfully.');
    }

    public function render()
    {
        return view('livewire.admin.label.labels-list', [
            'labels' => ProductLabel::query()
                ->withCount('products')
                ->when($this->search !== '', fn($q) => $q->where('text', 'like', '%' . $this->search . '%'))
                ->ordered()
                ->paginate(20),
            'icons' => ProductLabelIcons::all(),
            'canManageLabels' => $this->hasPermission('catalog.labels.manage'),
        ]);
    }
}
