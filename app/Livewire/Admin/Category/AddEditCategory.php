<?php

namespace App\Livewire\Admin\Category;

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\Language;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AddEditCategory extends Component
{
    use WithFileUploads;

    public ?int $categoryId = null;

    /**
     * Ordered list of selected category IDs forming the path to the chosen parent.
     * e.g. [3, 7] means root=3 → child=7. Last element becomes parent_id.
     */
    public array $parentPath = [];

    public string $status = 'draft';
    public bool $isFeatured = false;
    public array $translations = [];
    public string $activeLocale = 'en';

    public bool $removeThumbImage = false;

    /** @var TemporaryUploadedFile|null */
    public $thumbImage = null;

    public function mount(?Category $category = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => [
                'name' => '',
                'slug' => '',
                'description' => '',
                'meta_keywords' => '',
                'meta_description' => '',
            ],
        ])->all();

        if (!$category) {
            return;
        }

        $loaded = app(CategoryRepository::class)->findForEditor($category);
        $this->categoryId = $loaded->id;

        // Reconstruct the parentPath from root → parent
        if ($loaded->parent_id) {
            $current = Category::query()
                ->with(['parent.parent.parent.parent.translations.language'])
                ->find($loaded->parent_id);
            $path = [];
            while ($current) {
                array_unshift($path, $current->id);
                $current = $current->parent;
            }
            $this->parentPath = $path;
        }

        $this->status = $loaded->status->value;
        $this->isFeatured = $loaded->is_featured;

        $this->translations = array_replace_recursive(
            $this->translations,
            $loaded->translationsByLocale(['name', 'slug', 'description', 'meta_keywords', 'meta_description'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    /**
     * When a level in parentPath changes, truncate any deeper selections so the
     * cascade always reflects the current state.
     */
    public function updatedParentPath($value, ?string $key): void
    {
        if ($key === null || !preg_match('/^(\d+)$/', $key, $m)) {
            return;
        }
        $index = (int) $m[1];

        // Keep only levels 0 … index, then set the new value at this level
        $this->parentPath = array_slice($this->parentPath, 0, $index);
        if (filled($value)) {
            $this->parentPath[$index] = (int) $value;
        }
        $this->parentPath = array_values($this->parentPath);
    }

    public function removeThumb(): void
    {
        $this->removeThumbImage = true;
        $this->thumbImage = null;
    }

    public function save(CategoryService $service): mixed
    {
        $validated = $this->validate($this->rules());

        $category = $service->save([
            'parent_id' => !empty($this->parentPath) ? (int) end($this->parentPath) : null,
            'status' => $validated['status'],
            'is_featured' => $validated['isFeatured'],
            'translations' => $validated['translations'],
            'remove_thumb' => $validated['removeThumbImage'],
            'thumb_image' => $this->thumbImage,
        ], $this->categoryId ? Category::query()->findOrFail($this->categoryId) : null);

        session()->flash('status', $this->categoryId ? 'Category updated successfully.' : 'Category created successfully.');

        return redirect()->route('admin.categories.edit', $category);
    }

    protected function rules(): array
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'parentPath' => ['array'],
            'parentPath.*' => ['integer', Rule::exists('categories', 'id')],
            'status' => ['required', Rule::enum(CategoryStatus::class)],
            'isFeatured' => ['boolean'],
            'removeThumbImage' => ['boolean'],
            'thumbImage' => ['nullable', 'image', 'max:4096'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $nameRule = $language->code === $defaultLocale ? 'required' : 'nullable';

            $rules["translations.{$language->code}.name"] = [$nameRule, 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:150'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function render()
    {
        // Build a flat keyed collection and a children map for efficient traversal
        $allCats = Category::query()
            ->with('translations.language')
            ->when($this->categoryId, fn($q) => $q->where('id', '!=', $this->categoryId))
            ->get()
            ->keyBy('id');

        $childrenMap = $allCats->groupBy('parent_id');

        // Compute cascading levels for the parent selector
        $categoryLevels = [];
        $currentCats = $childrenMap->get(null, collect());
        $label = 'Parent Category';

        for ($i = 0; $i <= count($this->parentPath); $i++) {
            $selectedId = $this->parentPath[$i] ?? '';
            $categoryLevels[] = [
                'label' => $label,
                'categories' => $currentCats,
                'selected' => $selectedId,
                'index' => $i,
            ];

            if (!filled($selectedId)) {
                break;
            }

            $children = $childrenMap->get((int) $selectedId, collect());
            if ($children->isEmpty()) {
                break;
            }

            $selCat = $allCats->get((int) $selectedId);
            $label = 'Sub categories of ' . ($selCat?->translationValue('name') ?? $selCat?->slug ?? '');
            $currentCats = $children;
        }

        return view('livewire.admin.category.add-edit-category', [
            'pageTitle' => $this->categoryId ? 'Edit Category' : 'Add Category',
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
            'categoryLevels' => $categoryLevels,
            'statusOptions' => CategoryStatus::cases(),
            'existingThumb' => $this->categoryId
                ? Category::query()->with('files')->find($this->categoryId)?->thumb_url
                : null,
        ]);
    }
}
