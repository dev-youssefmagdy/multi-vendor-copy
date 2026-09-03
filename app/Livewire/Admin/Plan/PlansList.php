<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\PackageStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\PackageRepository;
use Livewire\Component;
use Livewire\WithPagination;

class PlansList extends Component
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

    public function deletePackage(int $packageId): void
    {
        $this->authorizePermission('plans.packages.manage');
        \App\Models\Package::query()->findOrFail($packageId)->delete();
        session()->flash('status', 'Package deleted successfully.');
    }

    public function render(PackageRepository $packages)
    {
        return view('livewire.admin.plan.plans-list', [
            'packages' => $packages->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
            ]),
            'stats' => $packages->stats(),
            'statusOptions' => PackageStatus::cases(),
            'canManagePackages' => $this->hasPermission('plans.packages.manage'),
        ]);
    }
}
