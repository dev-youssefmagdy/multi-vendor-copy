<?php

namespace App\Livewire\Tenant\Store;

use App\Models\Tenant\Page;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AddEditPage extends Component
{
    public ?int $pageId = null;
    public string $slug = '';
    public bool $active = true;
    public array $translations = [];
    public string $activeLocale = 'en';

    public function mount(?Page $page = null): void
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $this->activeLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn($language) => [
            $language->code => ['title' => '', 'body' => ''],
        ])->all();

        if (!$page) {
            return;
        }

        $page->load('translations.language');

        $this->pageId = $page->id;
        $this->slug = $page->slug;
        $this->active = $page->active;
        $this->translations = array_replace_recursive(
            $this->translations,
            $page->translationsByLocale(['title', 'body'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(TenantPanelService $service)
    {
        $validated = $this->validate($this->rules());

        $page = $service->savePage([
            'slug' => $validated['slug'] ?? null,
            'active' => $validated['active'],
            'default_locale' => $this->defaultLocale(),
            'translations' => $validated['translations'],
        ], $this->pageId ? Page::query()->findOrFail($this->pageId) : null);

        session()->flash('status', $this->pageId ? 'Page updated successfully.' : 'Page created successfully.');

        return redirect()->route('tenant.store.pages.edit', $page);
    }

    protected function rules(): array
    {
        $rules = [
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('pages', 'slug')->ignore($this->pageId)],
            'active' => ['boolean'],
        ];

        $defaultLocale = $this->defaultLocale();

        foreach (app(TenantPanelRepository::class)->activeLanguages() as $language) {
            $rules["translations.{$language->code}.title"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.body"] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function defaultLocale(): string
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();

        return $languages->firstWhere('is_default', true)?->code ?? $this->activeLocale;
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $repository = app(TenantPanelRepository::class);

        return view('livewire.tenant.store.add-edit-page', [
            'pageTitle' => $this->pageId ? 'Edit Page' : 'Add Page',
            'pageDescription' => $this->pageId
                ? 'Update storefront page settings and translated content.'
                : 'Create a new storefront page with translated content for tenant languages.',
            'languages' => $repository->activeLanguages(),
            'bodyContent' => data_get($this->translations, $this->activeLocale . '.body', ''),
        ]);
    }
}
