<?php

namespace App\Livewire\Admin\Shipping;

use App\Enums\ShippingZoneStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\ShippingZoneRepository;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryChargesList extends Component
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

    public function deleteShippingZone(int $zoneId): void
    {
        $this->authorizePermission('shipping.delivery.manage');
        \App\Models\ShippingZone::query()->findOrFail($zoneId)->delete();
        session()->flash('status', 'Shipping zone deleted successfully.');
    }

    public function render(ShippingZoneRepository $zones)
    {
        return view('livewire.admin.shipping.delivery-charges-list', [
            'zones' => $zones->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
            ]),
            'stats' => $zones->stats(),
            'statusOptions' => ShippingZoneStatus::cases(),
            'canManageShipping' => $this->hasPermission('shipping.delivery.manage'),
        ]);
    }
}
