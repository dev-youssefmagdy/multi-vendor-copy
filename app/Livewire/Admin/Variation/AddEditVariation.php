<?php

namespace App\Livewire\Admin\Variation;

use App\Enums\VariationStatus;
use App\Models\Language;
use App\Models\Variation;
use App\Repositories\VariationRepository;
use App\Services\VariationService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditVariation extends Component
{
    public ?int $variationId = null;
    public string $status = 'active';
    public array $translations = [];
    public array $options = [];
    public string $activeLocale = 'en';

    public function mount(?Variation $variation = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['name' => '', 'description' => ''],
        ])->all();

        if (!$variation) {
            $this->addOption();
            return;
        }

        $loaded = app(VariationRepository::class)->findForEditor($variation);
        $this->variationId = $loaded->id;
        $this->status = $loaded->status->value;

        $this->translations = array_replace_recursive(
            $this->translations,
            $loaded->translationsByLocale(['name', 'description'])
        );

        $this->options = $loaded->options->map(function ($option) use ($languages) {
            $translations = $languages->mapWithKeys(fn(Language $language) => [
                $language->code => ['name' => ''],
            ])->all();

            return [
                'id' => $option->id,
                'swatch' => $option->swatch ?? '',
                'translations' => array_replace_recursive($translations, $option->translationsByLocale(['name'])),
            ];
        })->all();

        if ($this->options === []) {
            $this->addOption();
        }
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function addOption(): void
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();

        $this->options[] = [
            'swatch' => '',
            'translations' => $languages->mapWithKeys(fn(Language $language) => [
                $language->code => ['name' => ''],
            ])->all(),
        ];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function save(VariationService $service): mixed
    {
        $validated = $this->validate($this->rules());

        $variation = $service->save([
            'status' => $validated['status'],
            'translations' => $validated['translations'],
            'options' => $validated['options'],
        ], $this->variationId ? Variation::query()->findOrFail($this->variationId) : null);

        session()->flash('status', $this->variationId ? 'Variation updated successfully.' : 'Variation created successfully.');

        return redirect()->route('admin.variations.edit', $variation);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'status' => ['required', Rule::enum(VariationStatus::class)],
            'options' => ['array', 'min:1'],
            'options.*.swatch' => ['nullable', 'string', 'max:20'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = $language->code === $defaultLocale ? 'required' : 'nullable';

            $rules["translations.{$language->code}.name"] = [$nameRule, 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["options.*.translations.{$language->code}.name"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.admin.variation.add-edit-variation', [
            'pageTitle' => $this->variationId ? 'Edit Variation' : 'Add Variation',
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get(),
            'statusOptions' => VariationStatus::cases(),
        ]);
    }
}
