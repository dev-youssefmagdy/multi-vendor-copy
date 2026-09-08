<?php

namespace App\Livewire\Admin\Plan;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Package;
use App\Models\PendingRegistration;
use Livewire\Component;
use Livewire\WithPagination;

class PendingRegistrationsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all'; // all | pending | completed | expired
    public string $packageFilter = '';

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

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'packageFilter']);
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function deleteRecord(int $id): void
    {
        $this->authorizePermission('plans.pending-registrations.manage');
        PendingRegistration::query()->findOrFail($id)->delete();
        session()->flash('status', 'Pending registration deleted.');
    }

    public function render()
    {
        $query = PendingRegistration::query()->with('package');

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if (filled($this->packageFilter)) {
            $query->where('package_id', $this->packageFilter);
        }

        match ($this->statusFilter) {
            'pending' => $query->whereNull('completed_at')->where('expires_at', '>', now()),
            'completed' => $query->whereNotNull('completed_at'),
            'expired' => $query->whereNull('completed_at')->where('expires_at', '<=', now()),
            default => null,
        };

        $records = $query->latest()->paginate(20);

        $total = PendingRegistration::count();
        $pending = PendingRegistration::whereNull('completed_at')->where('expires_at', '>', now())->count();
        $completed = PendingRegistration::whereNotNull('completed_at')->count();
        $expired = PendingRegistration::whereNull('completed_at')->where('expires_at', '<=', now())->count();

        return view('livewire.admin.plan.pending-registrations-list', [
            'records' => $records,
            'packages' => Package::query()->with('translations.language')->get(),
            'stats' => compact('total', 'pending', 'completed', 'expired'),
            'canManage' => $this->hasPermission('plans.pending-registrations.manage'),
        ]);
    }
}
