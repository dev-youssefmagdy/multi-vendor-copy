<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\LanguageDirection;
use App\Jobs\CopyLanguageCatalogJob;
use App\Jobs\TranslateLanguageCatalogJob;
use App\Models\Country;
use App\Models\Language;
use App\Services\LanguageService;
use App\Services\OpenAiTranslationService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditLanguage extends Component
{
    use WithFileUploads;
    public ?int $languageId = null;
    public string $name = '';
    public string $code = '';
    public string $nativeName = '';
    public string $direction = 'ltr';
    public bool $isDefault = false;
    public bool $isActive = true;
    public bool $isFree = true;
    public ?float $price = null;
    public ?float $aiTranslationPrice = null;
    public bool $offersAiTranslation = false;
    public bool $autoTranslateCatalog = true;
    public array $countries = [];
    public ?string $existingImageUrl = null;

    public $image = null;

    public bool $removeImage = false;

    public function mount(?Language $language = null)
    {
        if (!$language) {
            return;
        }

        $this->languageId = $language->id;
        $this->name = $language->name;
        $this->code = $language->code;
        $this->nativeName = $language->native_name;
        $this->direction = $language->direction->value;
        $this->isDefault = $language->is_default;
        $this->isActive = $language->is_active;
        $this->isFree = $language->is_free;
        $this->price = $language->price ? (float) $language->price : null;
        $this->offersAiTranslation = $language->ai_translation_price !== null;
        $this->aiTranslationPrice = $language->ai_translation_price !== null ? (float) $language->ai_translation_price : null;
        $this->countries = $language->countries ?? [];
        $language->loadMissing('imageFile');
        $this->existingImageUrl = $language->imageFile?->full_path;
    }

    public function save(LanguageService $service, OpenAiTranslationService $openAi)
    {
        $creating = $this->languageId === null;
        $sourceLocale = Language::query()->where('is_default', true)->value('code') ?? config('app.fallback_locale', 'en');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:12', Rule::unique('languages', 'code')->ignore($this->languageId)],
            'nativeName' => ['required', 'string', 'max:255'],
            'direction' => ['required', Rule::enum(LanguageDirection::class)],
            'isDefault' => ['boolean'],
            'isActive' => ['boolean'],
            'isFree' => ['boolean'],
            'price' => ['nullable', 'numeric', 'min:0.01', 'required_if:isFree,false'],
            'offersAiTranslation' => ['boolean'],
            'aiTranslationPrice' => ['nullable', 'numeric', 'min:0', 'required_if:offersAiTranslation,true'],
            'autoTranslateCatalog' => ['boolean'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'size:2'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $language = $service->save([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'native_name' => $validated['nativeName'],
            'direction' => $validated['direction'],
            'is_default' => $validated['isDefault'],
            'is_active' => $validated['isActive'],
            'is_free' => $validated['isFree'],
            'price' => $validated['isFree'] ? null : $validated['price'],
            'ai_translation_price' => $validated['offersAiTranslation'] ? (float) $validated['aiTranslationPrice'] : null,
            'countries' => $validated['countries'] ?? [],
            'auto_translate_catalog' => $validated['autoTranslateCatalog'],
            'image' => $this->image,
            'remove_image' => $this->removeImage,
        ], $this->languageId ? Language::query()->findOrFail($this->languageId) : null);

        if ($creating && strtolower($language->code) !== strtolower((string) $sourceLocale)) {
            if ($validated['autoTranslateCatalog'] && $openAi->configured()) {
                $language->forceFill(['translation_progress' => 0])->save();
                TranslateLanguageCatalogJob::dispatch($language->id, (string) $sourceLocale)->afterCommit();
                session()->flash('status', 'Language created successfully. Catalog and lang files translation has been queued.');
            } else {
                $language->forceFill(['translation_progress' => 0])->save();
                CopyLanguageCatalogJob::dispatch($language->id, (string) $sourceLocale)->afterCommit();
                session()->flash('status', 'Language created successfully. Catalog entries are being copied from the source language.');
            }
        } else {
            session()->flash('status', $this->languageId ? 'Language updated successfully.' : 'Language created successfully.');
        }

        return redirect()->route('admin.settings.languages.edit', $language);
    }

    public function removeImage(): void
    {
        $this->removeImage = true;
        $this->image = null;
        $this->existingImageUrl = null;
    }

    public function render()
    {
        $countryOptions = Country::query()
            ->orderBy('name')
            ->get(['name', 'iso2'])
            ->mapWithKeys(fn ($c) => [strtoupper($c->iso2) => $c->name])
            ->all();

        return view('livewire.admin.setting.add-edit-language', [
            'pageTitle' => $this->languageId ? 'Edit Language' : 'Add Language',
            'directionOptions' => LanguageDirection::cases(),
            'countryOptions' => $countryOptions,
        ]);
    }
}
