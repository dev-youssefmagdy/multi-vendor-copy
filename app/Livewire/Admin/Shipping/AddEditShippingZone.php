<?php

namespace App\Livewire\Admin\Shipping;

use App\Enums\ShippingZoneStatus;
use App\Models\Language;
use App\Models\ShippingZone;
use App\Repositories\ShippingZoneRepository;
use App\Services\ShippingZoneService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditShippingZone extends Component
{
    public ?int $shippingZoneId = null;
    public string $code = '';
    public string $currencyCode = 'USD';
    public string $status = 'active';
    public array $translations = [];
    public array $rates = [];
    public string $activeLocale = 'en';

    public function mount(?ShippingZone $shippingZone = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn (Language $language) => [
            $language->code => ['name' => '', 'description' => ''],
        ])->all();

        if (! $shippingZone) {
            $this->addRate();
            return;
        }

        $loaded = app(ShippingZoneRepository::class)->findForEditor($shippingZone);
        $this->shippingZoneId = $loaded->id;
        $this->code           = $loaded->code;
        $this->currencyCode   = $loaded->currency_code ?? 'USD';
        $this->status         = $loaded->status->value;

        $this->translations = array_replace_recursive(
            $this->translations,
            $loaded->translationsByLocale(['name', 'description'])
        );

        $this->rates = $loaded->rates->map(fn ($rate) => [
            'id'         => $rate->id,
            'name'       => $rate->name,
            'min_weight' => $rate->min_weight,
            'max_weight' => $rate->max_weight,
            'price'      => $rate->price,
            'is_active'  => $rate->is_active,
        ])->all();

        if ($this->rates === []) {
            $this->addRate();
        }
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function addRate(): void
    {
        $this->rates[] = [
            'name'       => '',
            'min_weight' => '',
            'max_weight' => '',
            'price'      => '0.00',
            'is_active'  => true,
        ];
    }

    public function removeRate(int $index): void
    {
        unset($this->rates[$index]);
        $this->rates = array_values($this->rates);
    }

    public function save(ShippingZoneService $service): mixed
    {
        $validated = $this->validate($this->rules());

        $zone = $service->save([
            'code'          => $validated['code'],
            'currency_code' => $validated['currencyCode'],
            'status'        => $validated['status'],
            'translations'  => $validated['translations'],
            'rates'         => $validated['rates'],
        ], $this->shippingZoneId ? ShippingZone::query()->findOrFail($this->shippingZoneId) : null);

        session()->flash('status', $this->shippingZoneId ? 'Shipping zone updated successfully.' : 'Shipping zone created successfully.');

        return redirect()->route('admin.shipping.delivery-charges.edit', $zone);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'code'              => ['required', 'string', 'max:120'],
            'currencyCode'      => ['required', 'string', 'size:3'],
            'status'            => ['required', Rule::enum(ShippingZoneStatus::class)],
            'rates'             => ['array'],
            'rates.*.name'       => ['required', 'string', 'max:255'],
            'rates.*.min_weight' => ['nullable', 'numeric', 'min:0'],
            'rates.*.max_weight' => ['nullable', 'numeric', 'min:0'],
            'rates.*.price'      => ['required', 'numeric', 'min:0'],
            'rates.*.is_active'  => ['boolean'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = $language->code === $defaultLocale ? 'required' : 'nullable';

            $rules["translations.{$language->code}.name"]        = [$nameRule, 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.admin.shipping.add-edit-shipping-zone', [
            'pageTitle'     => $this->shippingZoneId ? 'Edit Delivery Zone' : 'Add Delivery Zone',
            'languages'     => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
            'statusOptions' => ShippingZoneStatus::cases(),
        ]);
    }
}
