<?php

namespace App\Livewire\Admin\Branch;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Branch;
use App\Repositories\BranchRepository;
use Livewire\Component;
use Livewire\WithPagination;

class BranchesList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $activeFilter = '';
    public int $defaultFreeShippingWeight = 1500;

    public function mount(): void
    {
        $defaultBranch = Branch::query()->where('is_default', true)->first()
            ?? Branch::query()->first();

        $this->defaultFreeShippingWeight = (int) ($defaultBranch?->default_free_shipping_weight ?? 1500);
    }

    public function saveDefaultFreeShippingWeight(): void
    {
        $this->authorizePermission('branches.manage');

        $this->validate([
            'defaultFreeShippingWeight' => ['required', 'integer', 'min:0'],
        ]);

        $defaultBranch = Branch::query()->where('is_default', true)->first()
            ?? Branch::query()->first();

        if ($defaultBranch) {
            $defaultBranch->update([
                'default_free_shipping_weight' => $this->defaultFreeShippingWeight,
            ]);
            session()->flash('status', 'Default free shipping weight updated.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'activeFilter']);
        $this->resetPage();
    }

    public function deleteBranch(int $branchId): void
    {
        $this->authorizePermission('branches.manage');
        $branch = \App\Models\Branch::query()->findOrFail($branchId);

        if ($branch->is_default) {
            session()->flash('error', 'Cannot delete the default branch.');
            return;
        }

        app(\App\Services\BranchService::class)->delete($branch);
        session()->flash('status', 'Branch deleted successfully.');
    }

    public function render(BranchRepository $branches)
    {
        return view('livewire.admin.branch.branches-list', [
            'branches' => $branches->paginate([
                'search' => $this->search,
                'is_active' => $this->activeFilter,
            ]),
            'stats' => $branches->stats(),
            'canManage' => $this->hasPermission('branches.manage'),
        ]);
    }
}
