<?php

namespace App\Livewire\Admin\Page;

use App\Enums\ContentStatus;
use App\Models\Language;
use App\Models\StaticPage;
use App\Services\StaticPageService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditStaticPage extends Component
{
    public ?int $pageId = null;
    public string $status = 'draft';
    public bool $isCompliance = false;
    public array $translations = [];
    public string $activeLocale = 'en';

    public function mount(?StaticPage $staticPage = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['title' => '', 'slug' => '', 'content' => ''],
        ])->all();

        if (!$staticPage || !$staticPage->exists) {
            return;
        }

        $this->pageId = $staticPage->id;
        $this->status = $staticPage->status->value;
        $this->isCompliance = (bool) $staticPage->is_compliance;

        $this->translations = array_replace_recursive(
            $this->translations,
            $staticPage->load('translations.language')->translationsByLocale(['title', 'slug', 'content'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(StaticPageService $service): mixed
    {
        $pageId = $this->pageId;
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'isCompliance' => ['boolean'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $isDefault = $language->code === $defaultLocale;
            $rules["translations.{$language->code}.title"] = [$isDefault ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.content"] = ['nullable', 'string'];
        }

        $validated = $this->validate($rules);

        $service->save([
            'status' => $validated['status'],
            'is_compliance' => $validated['isCompliance'] ?? false,
            'translations' => $validated['translations'],
        ], $pageId ? StaticPage::query()->findOrFail($pageId) : null);

        session()->flash('status', $pageId ? 'Static page updated successfully.' : 'Static page created successfully.');

        return redirect()->route('admin.pages.index');
    }

    public function render()
    {
        return view('livewire.admin.pages.add-edit-static-page', [
            'pageTitle' => $this->pageId ? 'Edit Static Page' : 'Add Static Page',
            'statusOptions' => ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'],
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
        ]);
    }
}
