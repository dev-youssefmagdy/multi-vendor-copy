<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\AppSetting;
use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use Livewire\Component;
use Livewire\WithPagination;

class CurrenciesPage extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    public function deleteCurrency(int $currencyId): void
    {
        $this->authorizePermission('settings.currencies.manage');

        $currency = Currency::query()->findOrFail($currencyId);
        $defaultCode = (string) (AppSetting::query()->where('key', 'default_currency')->value('value') ?? 'USD');

        if ($currency->code === $defaultCode) {
            session()->flash('status', 'Set another default currency before deleting this one.');

            return;
        }

        $currency->delete();
        session()->flash('status', 'Currency deleted successfully.');
    }

    public function render(CurrencyRepository $currencies)
    {
        return view('livewire.admin.setting.currencies-page', [
            'currencies' => $currencies->paginate([
                'search' => $this->search,
            ]),
            'stats' => $currencies->stats(),
            'canManageCurrencies' => $this->hasPermission('settings.currencies.manage'),
        ]);
    }
}
