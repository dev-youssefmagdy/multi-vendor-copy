<?php

namespace App\Livewire\Tenant\Category;

use App\Models\Tenant\Category;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantCategoryMediaService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditCategory extends Component
{
    use WithFileUploads;

    public ?int $categoryId = null;
    public ?int $centralCategoryId = null;
    public ?int $parentId = null;
    public int $orderNumber = 0;
    public bool $active = true;
    public bool $featured = false;
    public array $translations = [];
    public string $activeLocale = 'en';
    public $thumbUpload = null;
    public bool $removeThumb = false;
    public ?string $currentThumbUrl = null;

    public function mount(?Category $category = null): void
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $this->activeLocale = $languages->first()?->code ?? 'en';
        $this->translations = $languages->mapWithKeys(fn($language) => [$language->code => ['name' => '', 'slug' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => '']])->all();

        if (!$category) {
            return;
        }

        $category->load(['translations.language', 'files']);
        $this->categoryId = $category->id;
        $this->centralCategoryId = $category->central_category_id;
        $this->parentId = $category->parent_id;
        $this->orderNumber = $category->order_number;
        $this->active = $category->active;
        $this->featured = $category->featured;
        $this->translations = array_replace_recursive($this->translations, $category->translationsByLocale(['name', 'slug', 'description', 'meta_keywords', 'meta_description']));

        $ownThumb = $category->files->firstWhere('key', 'thumb');
        $this->currentThumbUrl = $ownThumb?->full_path;
    }

    public function updatedThumbUpload(): void
    {
        $this->validate(['thumbUpload' => ['nullable', 'image', 'max:4096']]);
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(TenantPanelService $service)
    {
        try {
            $validated = $this->validate($this->rules());
        } catch (ValidationException $e) {
            $this->dispatch('admin-toast', message: 'Please fill in all required category details before saving.', type: 'error');

            throw $e;
        }

        if ($validated['centralCategoryId'] ?? null) {
            $snapshot = app(TenantPanelRepository::class)->centralCategorySnapshot((int) $validated['centralCategoryId']);

            if (!$snapshot) {
                $this->addError('centralCategoryId', 'The linked central category could not be found.');
                $this->dispatch('admin-toast', message: 'The linked central category could not be found.', type: 'error');

                return null;
            }
        }

        $category = $service->saveCategory([
            'central_category_id' => $validated['centralCategoryId'] ?? null,
            'parent_id' => $validated['parentId'] ?? null,
            'order_number' => $validated['orderNumber'],
            'active' => $validated['active'],
            'featured' => $validated['featured'],
            'translations' => $validated['translations'],
            'default_locale' => $this->activeLocale,
        ], $this->categoryId ? Category::query()->findOrFail($this->categoryId) : null);

        $mediaService = app(TenantCategoryMediaService::class);

        if ($this->thumbUpload) {
            $mediaService->syncThumb($category, $this->thumbUpload);
        } elseif ($this->removeThumb) {
            $mediaService->purgeThumb($category);
        }

        session()->flash('status', $this->categoryId ? 'Category updated successfully.' : 'Category created successfully.');

        return redirect()->route('tenant.categories.edit', $category);
    }

    protected function rules(): array
    {
        $rules = [
            'centralCategoryId' => ['nullable', 'integer'],
            'parentId' => ['nullable', 'integer', 'exists:categories,id'],
            'orderNumber' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'thumbUpload' => ['nullable', 'image', 'max:4096'],
            'removeThumb' => ['boolean'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? 'en';

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:150'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $repository = app(TenantPanelRepository::class);
        $centralCategory = $repository->centralCategorySnapshot($this->centralCategoryId);

        return view('livewire.tenant.category.add-edit-category', [
            'pageTitle' => $this->categoryId ? 'Edit Category' : 'Add Category',
            'pageDescription' => $this->categoryId ? 'Update storefront category hierarchy and translations.' : 'Create a tenant category from the selected catalog scope.',
            'languages' => $repository->activeLanguages(),
            'parents' => $repository->categoryOptions(),
            'centralCategory' => $centralCategory,
            'currentThumbUrl' => $this->currentThumbUrl,
        ]);
    }
}
