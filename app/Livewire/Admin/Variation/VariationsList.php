<?php

namespace App\Livewire\Admin\Variation;

use App\Enums\VariationStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\VariationRepository;
use Livewire\Component;
use Livewire\WithPagination;

class VariationsList extends Component
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

    public function deleteVariation(int $variationId): void
    {
        $this->authorizePermission('catalog.variations.manage');
        \App\Models\Variation::query()->findOrFail($variationId)->delete();
        session()->flash('status', 'Variation deleted successfully.');
    }

    public function render(VariationRepository $variations)
    {
        return view('livewire.admin.variation.variations-list', [
            'variations' => $variations->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
            ]),
            'stats' => $variations->stats(),
            'statusOptions' => VariationStatus::cases(),
            'canManageVariations' => $this->hasPermission('catalog.variations.manage'),
        ]);
    }
}
