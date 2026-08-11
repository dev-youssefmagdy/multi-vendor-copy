<?php

namespace App\Livewire\Admin\Shipping;

use App\Jobs\SyncProductFixedShippingCosts;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\FixedShippingCost;
use App\Repositories\FixedShippingCostRepository;
use Livewire\Component;
use Livewire\WithPagination;

class FixedShippingCostsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $countryFilter = '';

    public function updatedCountryFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('countryFilter');
        $this->resetPage();
    }

    public function deleteFixedShippingCost(int $id): void
    {
        $this->authorizePermission('shipping.delivery.manage');
        FixedShippingCost::query()->findOrFail($id)->delete();
        SyncProductFixedShippingCosts::dispatch();
        session()->flash('status', 'Fixed shipping cost deleted.');
    }

    public function render(FixedShippingCostRepository $repository)
    {
        return view('livewire.admin.shipping.fixed-shipping-costs-list', [
            'costs' => $repository->paginate(['country_search' => $this->countryFilter ?: null]),
            'stats' => $repository->stats(),
            'canManage' => $this->hasPermission('shipping.delivery.manage'),
        ]);
    }
}
