<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\PackageStatus;
use App\Models\Language;
use App\Models\Package;
use App\Repositories\PackageRepository;
use App\Services\PackageService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditPackage extends Component
{
    public ?int $packageId = null;
    public string $status = 'draft';
    public string $term = 'monthly';
    public string $price = '0.00';
    public int $trialDays = 0;
    public array $featureRows = [];
    public array $translations = [];
    public string $activeLocale = 'en';
    public string $icon = '';
    public int $categoriesCount = 0;
    public bool $allCategories = true; // true = no limit, false = limited count
    public int $productsLimit = -1;
    public int $bannersLimit = -1;
    public int $languagesLimit = -1;
    public int $ordersPerMonthLimit = -1;
    public int $aiCallsLimit = -1;
    public int $imageSearchesLimit = -1;

    public function mount(?Package $package = null)
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['name' => '', 'description' => ''],
        ])->all();

        if (!$package) {
            $this->ensureFeatureRows();
            return;
        }

        $loaded = app(PackageRepository::class)->findForEditor($package);
        $this->packageId = $loaded->id;
        $this->status = $loaded->status->value;
        $this->term = $loaded->term->value;
        $this->price = number_format((float) $loaded->price, 2, '.', '');
        $this->trialDays = $loaded->trial_days;
        $this->featureRows = collect((array) $loaded->features)
            ->map(fn(mixed $value, string $label) => [
                'label' => $label,
                'value' => $this->stringifyFeatureValue($value),
            ])
            ->values()
            ->all();
        $this->ensureFeatureRows();
        $this->icon = $loaded->icon ?? '';
        $this->categoriesCount = (int) ($loaded->categories_count ?? 0);
        $this->allCategories = ($this->categoriesCount === 0);
        $this->productsLimit = (int) ($loaded->products_limit ?? -1);
        $this->bannersLimit = (int) ($loaded->banners_limit ?? -1);
        $this->languagesLimit = (int) ($loaded->languages_limit ?? -1);
        $this->ordersPerMonthLimit = (int) ($loaded->orders_per_month_limit ?? -1);
        $this->aiCallsLimit = (int) ($loaded->ai_calls_limit ?? -1);
        $this->imageSearchesLimit = (int) ($loaded->image_searches_limit ?? -1);
        $this->translations = array_replace_recursive($this->translations, $loaded->translationsByLocale(['name', 'description']));
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function updatedAllCategories(bool $value): void
    {
        if ($value) {
            $this->categoriesCount = 0;
        } elseif ($this->categoriesCount === 0) {
            $this->categoriesCount = 1;
        }
    }

    public function addFeatureRow(): void
    {
        $this->featureRows[] = $this->blankFeatureRow();
    }

    public function removeFeatureRow(int $index): void
    {
        unset($this->featureRows[$index]);
        $this->featureRows = array_values($this->featureRows);
        $this->ensureFeatureRows();
    }

    public function save(PackageService $service)
    {
        $validated = $this->validate($this->rules());
        $package = $service->save([
            'status' => $validated['status'],
            'term' => $this->term,
            'icon' => $this->icon,
            'price' => $validated['price'],
            'features' => $this->normalizeFeatures(),
            'categories_count' => $this->allCategories ? 0 : $this->categoriesCount,
            'products_limit' => $validated['productsLimit'],
            'banners_limit' => $validated['bannersLimit'],
            'languages_limit' => $validated['languagesLimit'],
            'orders_per_month_limit' => $validated['ordersPerMonthLimit'],
            'ai_calls_limit' => $validated['aiCallsLimit'],
            'image_searches_limit' => $validated['imageSearchesLimit'],
            'trial_days' => $validated['trialDays'],
            'translations' => $validated['translations'],
        ], $this->packageId ? Package::query()->findOrFail($this->packageId) : null);

        session()->flash('status', $this->packageId ? 'Package updated successfully.' : 'Package created successfully.');

        return redirect()->route('admin.plans.edit', $package);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
        $rules = [
            'status' => ['required', Rule::enum(PackageStatus::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'trialDays' => ['nullable', 'integer', 'min:0'],
            'featureRows' => ['array'],
            'featureRows.*.label' => ['nullable', 'string', 'max:255'],
            'featureRows.*.value' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'productsLimit' => ['required', 'integer', 'min:-1'],
            'bannersLimit' => ['required', 'integer', 'min:-1'],
            'languagesLimit' => ['required', 'integer', 'min:-1'],
            'ordersPerMonthLimit' => ['required', 'integer', 'min:-1'],
            'aiCallsLimit' => ['required', 'integer', 'min:-1'],
            'imageSearchesLimit' => ['required', 'integer', 'min:-1'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = ['nullable', 'string', 'max:255'];
            if ($language->code === $defaultLocale) {
                $nameRule[0] = 'required';
            }
            $rules["translations.{$language->code}.name"] = $nameRule;
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
        }

        return $rules;
    }

    private function ensureFeatureRows(): void
    {
        if ($this->featureRows === []) {
            $this->featureRows[] = $this->blankFeatureRow();
        }
    }

    private function blankFeatureRow(): array
    {
        return ['label' => '', 'value' => ''];
    }

    private function normalizeFeatures(): array
    {
        $features = [];

        foreach ($this->featureRows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label === '') {
                continue;
            }

            $features[$label] = $this->parseFeatureValue($value);
        }

        return $features;
    }

    private function parseFeatureValue(string $value): mixed
    {
        if ($value === '') {
            return true;
        }

        return match (strtolower($value)) {
            'true', 'yes', 'included', 'available' => true,
            'false', 'no', 'excluded', 'not included' => false,
            'unlimited', 'infinity', '∞' => 'Unlimited',
            default => $value,
        };
    }

    private function stringifyFeatureValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }

    public function render()
    {
        return view('livewire.admin.plan.add-edit-package', [
            'pageTitle' => $this->packageId ? 'Edit Package' : 'Add Package',
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get(),
            'statusOptions' => PackageStatus::cases(),
            'termOptions' => [
                ['value' => 'monthly', 'label' => 'Monthly', 'description' => 'Billed every month', 'icon' => 'fas fa-calendar-alt'],
                // ['value' => 'quarterly', 'label' => 'Quarterly', 'description' => 'Billed every 3 months', 'icon' => 'fas fa-calendar-week'],
                ['value' => 'yearly', 'label' => 'Yearly', 'description' => 'Billed once a year', 'icon' => 'fas fa-calendar'],
                // ['value' => 'lifetime', 'label' => 'Lifetime', 'description' => 'One-time payment', 'icon' => 'fas fa-infinity'],
            ],
            'iconOptions' => $this->iconOptions(),
        ]);
    }

    function iconOptions(): array
    {
        return [
            'fas fa-star' => 'Star',
            'fas fa-gem' => 'Gem',
            'fas fa-crown' => 'Crown',
            'fas fa-rocket' => 'Rocket',
            'fas fa-award' => 'Award',
            'fas fa-bolt' => 'Bolt',
            'fas fa-heart' => 'Heart',
            'fas fa-tags' => 'Tags',
            'fas fa-briefcase' => 'Business',
            'fas fa-box-open' => 'Package',
            'fas fa-layer-group' => 'Layers',
            'fas fa-chart-line' => 'Growth',
            'fas fa-users' => 'Users',
            'fas fa-shield-alt' => 'Security',
            'fas fa-cogs' => 'Settings',
            'fas fa-cloud' => 'Cloud',
            'fas fa-diamond' => 'Diamond',
            'fas fa-apple-alt' => 'Apple',
            'fas fa-leaf' => 'Leaf',
            'fas fa-moon' => 'Moon',
            'fas fa-sun' => 'Sun',
            'fas fa-star-half-alt' => 'Half Star',
            'fas fa-smile' => 'Smile',
            'fas fa-fingerprint' => 'Fingerprint',
            'fas fa-globe' => 'Globe',
            'fas fa-headset' => 'Support',
            'fas fa-infinity' => 'Infinity',
        ];
    }
}
