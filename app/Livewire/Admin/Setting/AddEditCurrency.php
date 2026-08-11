<?php

namespace App\Livewire\Admin\Setting;

use App\Models\AppSetting;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditCurrency extends Component
{
    public ?int $currencyId = null;
    public string $code = '';
    public string $name = '';
    public string $sign = '';
    public string $conversionRate = '1.000000';
    public bool $isDefault = false;

    public function mount(?Currency $currency = null): void
    {
        if (!$currency) {
            return;
        }

        $defaultCode = (string) (AppSetting::query()->where('key', 'default_currency')->value('value') ?? 'USD');

        $this->currencyId = $currency->id;
        $this->code = $currency->code ?? '';
        $this->name = $currency->name;
        $this->sign = $currency->sign ?? '';
        $this->conversionRate = number_format((float) $currency->conversion_rate, 6, '.', '');
        $this->isDefault = $currency->code === $defaultCode;
    }

    public function save(CurrencyService $service)
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($this->currencyId)],
            'name' => ['required', 'string', 'max:255'],
            'sign' => ['nullable', 'string', 'max:12'],
            'conversionRate' => ['required', 'numeric', 'gt:0'],
            'isDefault' => ['boolean'],
        ]);

        $currency = $service->save([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'sign' => $validated['sign'] ?: null,
            'conversion_rate' => $validated['conversionRate'],
            'is_default' => $validated['isDefault'],
        ], $this->currencyId ? Currency::query()->findOrFail($this->currencyId) : null);

        session()->flash('status', $this->currencyId ? 'Currency updated successfully.' : 'Currency created successfully.');

        return redirect()->route('admin.settings.currencies.edit', $currency);
    }

    public function render()
    {
        return view('livewire.admin.setting.add-edit-currency', [
            'pageTitle' => $this->currencyId ? 'Edit Currency' : 'Add Currency',
        ]);
    }
}
