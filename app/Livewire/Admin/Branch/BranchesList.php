<?php

namespace App\Livewire\Admin\Branch;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\BranchRepository;
use Livewire\Component;
use Livewire\WithPagination;

class BranchesList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $activeFilter = '';

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
