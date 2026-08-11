<?php

namespace App\Livewire\Admin\Shipping;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Country;
use App\Models\FixedShippingCost;
use App\Services\FixedShippingCostService;
use Livewire\Component;

class AddEditFixedShippingCost extends Component
{
    use AuthorizesAdminPermissions;

    public ?int $fixedShippingCostId = null;
    public string $countryId = '';
    public string $pricePerGram = '0.000000';
    public string $currencyCode = 'USD';
    public bool $isActive = true;

    public function mount(?FixedShippingCost $fixedShippingCost = null): void
    {
        if (!$fixedShippingCost || !$fixedShippingCost->exists) {
            return;
        }

        $this->fixedShippingCostId = $fixedShippingCost->id;
        $this->countryId = (string) $fixedShippingCost->country_id;
        $this->pricePerGram = (string) $fixedShippingCost->price_per_gram;
        $this->currencyCode = $fixedShippingCost->currency_code;
        $this->isActive = $fixedShippingCost->is_active;
    }

    public function save(FixedShippingCostService $service): mixed
    {
        $this->authorizePermission('shipping.delivery.manage');

        $uniqueCountryRule = $this->fixedShippingCostId
            ? 'unique:fixed_shipping_costs,country_id,' . $this->fixedShippingCostId
            : 'unique:fixed_shipping_costs,country_id';

        $this->validate([
            'countryId' => ['required', 'integer', 'exists:countries,id', $uniqueCountryRule],
            'pricePerGram' => ['required', 'numeric', 'min:0'],
            'currencyCode' => ['required', 'string', 'size:3'],
            'isActive' => ['boolean'],
        ]);

        $existing = $this->fixedShippingCostId
            ? FixedShippingCost::query()->findOrFail($this->fixedShippingCostId)
            : null;

        $service->save([
            'country_id' => $this->countryId,
            'price_per_gram' => $this->pricePerGram,
            'currency_code' => $this->currencyCode,
            'is_active' => $this->isActive,
        ], $existing);

        session()->flash(
            'status',
            $this->fixedShippingCostId
            ? 'Fixed shipping cost updated successfully.'
            : 'Fixed shipping cost created successfully.'
        );

        return redirect()->route('admin.shipping.fixed-costs');
    }

    public function render()
    {
        return view('livewire.admin.shipping.add-edit-fixed-shipping-cost', [
            'countries' => Country::query()->with('translations.language')->orderBy('name')->get(['id', 'name', 'iso2', 'flag_emoji']),
            'pageTitle' => $this->fixedShippingCostId ? 'Edit Fixed Shipping Cost' : 'Add Fixed Shipping Cost',
        ]);
    }
}
