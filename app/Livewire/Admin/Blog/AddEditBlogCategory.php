<?php

namespace App\Livewire\Admin\Blog;

use App\Enums\ContentStatus;
use App\Models\BlogCategory;
use App\Models\Language;
use App\Services\BlogCategoryService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditBlogCategory extends Component
{
    public ?int $categoryId = null;
    public string $status = 'draft';
    public array $translations = [];
    public string $activeLocale = 'en';

    public function mount(?BlogCategory $blogCategory = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['name' => '', 'slug' => ''],
        ])->all();

        if (!$blogCategory || !$blogCategory->exists) {
            return;
        }

        $this->categoryId = $blogCategory->id;
        $this->status = $blogCategory->status->value;

        $this->translations = array_replace_recursive(
            $this->translations,
            $blogCategory->load('translations.language')->translationsByLocale(['name', 'slug'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(BlogCategoryService $service): mixed
    {
        $categoryId = $this->categoryId;
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $isDefault = $language->code === $defaultLocale;
            $rules["translations.{$language->code}.name"] = [$isDefault ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:255'];
        }

        $validated = $this->validate($rules);

        $category = $service->save([
            'status' => $validated['status'],
            'translations' => $validated['translations'],
        ], $categoryId ? BlogCategory::query()->findOrFail($categoryId) : null);

        session()->flash('status', $categoryId ? 'Blog category updated successfully.' : 'Blog category created successfully.');

        return redirect()->route('admin.blog.categories.edit', $category);
    }

    public function render()
    {
        return view('livewire.admin.blog.add-edit-blog-category', [
            'pageTitle' => $this->categoryId ? 'Edit Blog Category' : 'Add Blog Category',
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
            'statusOptions' => ['draft' => 'Draft', 'active' => 'Active'],
        ]);
    }
}
