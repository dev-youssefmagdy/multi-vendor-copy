<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Language;
use App\Repositories\CatalogRepository;
use App\Services\CatalogService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditCatalog extends Component
{
    public ?int $catalogId = null;
    public string $slug = '';
    public string $status = 'active';
    public array $translations = [];
    public string $activeLocale = 'en';
    public array $parentCategoryIds = [];

    public function mount(?Catalog $catalog = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['name' => ''],
        ])->all();

        if (!$catalog) {
            return;
        }

        $loaded = app(CatalogRepository::class)->findForEditor($catalog);
        $this->catalogId = $loaded->id;
        $this->slug = $loaded->slug;
        $this->status = $loaded->status;
        $this->translations = array_replace_recursive($this->translations, $loaded->translationsByLocale(['name']));

        // Load only root (parent_id = null) categories that are directly assigned to this catalog
        $this->parentCategoryIds = $loaded->categories()
            ->whereNull('parent_id')
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(CatalogService $service)
    {
        $validated = $this->validate($this->rules());
        $catalog = $service->save([
            'slug' => $validated['slug'] ?? null,
            'status' => $validated['status'],
            'translations' => $validated['translations'],
            'parent_category_ids' => array_map('intval', $validated['parentCategoryIds']),
        ], $this->catalogId ? Catalog::query()->findOrFail($this->catalogId) : null);

        session()->flash('status', $this->catalogId ? 'Catalog updated successfully.' : 'Catalog created successfully.');

        return redirect()->route('admin.catalogs.edit', $catalog);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
        $rules = [
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('catalogs', 'slug')->ignore($this->catalogId)],
            'status' => ['required', 'string', 'max:50'],
            'parentCategoryIds' => ['nullable', 'array'],
            'parentCategoryIds.*' => ['integer', Rule::exists('categories', 'id')],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $rules["translations.{$language->code}.name"] = [
                $language->code === $defaultLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.admin.catalog.add-edit-catalog', [
            'pageTitle' => $this->catalogId ? 'Edit Catalog' : 'Add Catalog',
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
            'statusOptions' => ['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'],
            'rootCategories' => Category::query()
                ->with('translations.language')
                ->whereNull('parent_id')
                
                ->get(),
        ]);
    }
}
