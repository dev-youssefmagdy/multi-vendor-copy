<?php

namespace App\Livewire\Admin\Blog;

use App\Enums\ContentStatus;
use App\Models\BlogPost;
use App\Models\Language;
use App\Repositories\BlogPostRepository;
use App\Services\BlogPostService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditBlogPost extends Component
{
    use WithFileUploads;

    public ?int $postId = null;
    public ?string $blogCategoryId = null;
    public string $status = 'draft';
    public string $publishedAt = '';
    public array $translations = [];
    public string $activeLocale = 'en';
    public $image = null;
    public ?string $existingImageUrl = null;
    public bool $removeImage = false;

    public function mount(?BlogPost $post = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['title' => '', 'slug' => '', 'excerpt' => '', 'content' => ''],
        ])->all();

        if (!$post || !$post->exists) {
            return;
        }

        $this->postId = $post->id;
        $this->blogCategoryId = $post->blog_category_id ? (string) $post->blog_category_id : null;
        $this->status = $post->status->value;
        $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->existingImageUrl = $post->files()->where('key', 'image')->first()?->full_path;

        $this->translations = array_replace_recursive(
            $this->translations,
            $post->load('translations.language')->translationsByLocale(['title', 'slug', 'excerpt', 'content'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function removeImage(): void
    {
        $this->removeImage = true;
        $this->image = null;
        $this->existingImageUrl = null;
    }

    public function save(BlogPostService $service): mixed
    {
        $postId = $this->postId;
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'blogCategoryId' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'publishedAt' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $isDefault = $language->code === $defaultLocale;
            $rules["translations.{$language->code}.title"] = [$isDefault ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.excerpt"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.content"] = ['nullable', 'string'];
        }

        $validated = $this->validate($rules);

        $service->save([
            'blog_category_id' => $validated['blogCategoryId'] ?? null,
            'status' => $validated['status'],
            'published_at' => filled($validated['publishedAt']) ? Carbon::parse($validated['publishedAt']) : null,
            'translations' => $validated['translations'],
            'image' => $this->image,
            'remove_image' => $this->removeImage,
        ], $postId ? BlogPost::query()->findOrFail($postId) : null);

        session()->flash('status', $postId ? 'Blog post updated successfully.' : 'Blog post created successfully.');

        return redirect()->route('admin.blog.posts');
    }

    public function render()
    {
        return view('livewire.admin.blog.add-edit-blog-post', [
            'pageTitle' => $this->postId ? 'Edit Blog Post' : 'Add Blog Post',
            'categoryOptions' => ['' => 'Uncategorized'] + app(BlogPostRepository::class)->categoryOptions(),
            'statusOptions' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'],
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
        ]);
    }
}
