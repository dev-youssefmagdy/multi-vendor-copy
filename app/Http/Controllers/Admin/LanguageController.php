<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LanguageDirection;
use App\Http\Controllers\Controller;
use App\Jobs\CopyLanguageCatalogJob;
use App\Jobs\TranslateLanguageCatalogJob;
use App\Models\Country;
use App\Models\Language;
use App\Services\LanguageService;
use App\Services\OpenAiTranslationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LanguageController extends Controller
{
    public function create()
    {
        return view('admin.setting.add-edit-language', $this->viewData());
    }

    public function edit(Language $language)
    {
        $language->loadMissing('imageFile');

        return view('admin.setting.add-edit-language', $this->viewData($language));
    }

    public function store(Request $request, LanguageService $service, OpenAiTranslationService $openAi)
    {
        return $this->processForm($request, null, $service, $openAi);
    }

    public function update(Request $request, Language $language, LanguageService $service, OpenAiTranslationService $openAi)
    {
        return $this->processForm($request, $language, $service, $openAi);
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function processForm(Request $request, ?Language $language, LanguageService $service, OpenAiTranslationService $openAi)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:12', Rule::unique('languages', 'code')->ignore($language?->id)],
                'native_name' => ['required', 'string', 'max:255'],
                'direction' => ['required', Rule::enum(LanguageDirection::class)],
                'is_default' => ['boolean'],
                'is_active' => ['boolean'],
                'is_free' => ['boolean'],
                'price' => ['nullable', 'numeric', 'min:0.01', 'required_if:is_free,0'],
                // 'auto_translate_catalog' => ['boolean'],
                'countries' => ['nullable', 'array'],
                'countries.*' => ['string', 'size:2'],
                'image' => ['nullable', 'image', 'max:2048'],
                'remove_image' => ['boolean'],
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('status', 'Please fix the highlighted fields before saving.')
                ->with('status_type', 'error');
        }

        $creating = $language === null;
        $sourceLocale = Language::query()->where('is_default', true)->value('code') ?? config('app.fallback_locale', 'en');

        $autoTranslateCatalog = ($validated['auto_translate_catalog'] ?? 'on') === 'on';

        $savedLanguage = $service->save([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'native_name' => $validated['native_name'],
            'direction' => $validated['direction'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'is_free' => (bool) ($validated['is_free'] ?? true),
            'price' => ($validated['is_free'] ?? true) ? null : $validated['price'],
            'countries' => $validated['countries'] ?? [],
            'auto_translate_catalog' => $autoTranslateCatalog,
            'image' => $request->file('image'),
            'remove_image' => (bool) ($validated['remove_image'] ?? false),
        ], $language);

        if ($creating && strtolower($savedLanguage->code) !== strtolower((string) $sourceLocale)) {
            if ($autoTranslateCatalog && $openAi->configured()) {
                $savedLanguage->forceFill(['translation_progress' => 0])->save();
                TranslateLanguageCatalogJob::dispatch($savedLanguage->id, (string) $sourceLocale)->afterCommit();
                $message = 'Language created successfully. Catalog and lang files translation has been queued.';
            } else {
                $savedLanguage->forceFill(['translation_progress' => 0])->save();
                CopyLanguageCatalogJob::dispatch($savedLanguage->id, (string) $sourceLocale)->afterCommit();
                $message = 'Language created successfully. Catalog entries are being copied from the source language.';
            }
        } else {
            $message = $creating ? 'Language created successfully.' : 'Language updated successfully.';
        }

        return redirect()->route('admin.settings.languages.edit', $savedLanguage)
            ->with('status', $message)
            ->with('status_type', 'success');
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function viewData(?Language $language = null): array
    {
        $countryOptions = Country::query()
            ->orderBy('name')
            ->get(['name', 'iso2'])
            ->mapWithKeys(fn($c) => [strtoupper($c->iso2) => $c->name])
            ->all();

        return [
            'language' => $language,
            'pageTitle' => $language ? 'Edit Language' : 'Add Language',
            'directionOptions' => LanguageDirection::cases(),
            'countryOptions' => $countryOptions,
        ];
    }
}
