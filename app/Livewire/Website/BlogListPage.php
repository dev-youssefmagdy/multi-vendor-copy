<?php

namespace App\Livewire\Website;

use App\Enums\ContentStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Component;
use Livewire\WithPagination;

class BlogListPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryId = '';

    protected array $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['as' => 'category', 'except' => ''],
    ];

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->categoryId = (string) request('category', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $baseQuery = BlogPost::query()
            ->with(['translations.language', 'category.translations.language'])
            ->where('status', ContentStatus::Published->value)
            ->where(function ($q) {
                $q->where('published_at', '<=', now())
                    ->orWhereNull('published_at');
            })
            ->when(filled($this->search), function ($query) {
                $search = trim($this->search);

                $query->whereHas('translations', function ($nested) use ($search) {
                    $nested->whereIn('field', ['title', 'excerpt', 'content'])
                        ->where('value', 'like', "%{$search}%");
                });
            })
            ->when(filled($this->categoryId), fn($query) => $query->where('blog_category_id', (int) $this->categoryId));

        $featuredPost = blank($this->search) && blank($this->categoryId)
            ? (clone $baseQuery)->orderByDesc('published_at')->first()
            : null;

        $posts = (clone $baseQuery)
            ->when($featuredPost, fn($query) => $query->whereKeyNot($featuredPost->id))
            ->orderByDesc('published_at')
            ->paginate(9);

        $categories = BlogCategory::query()
            ->with('translations.language')
            ->whereIn('status', [ContentStatus::Active->value, ContentStatus::Published->value])
            ->get()
            ->sortBy('name')
            ->values();

        return view('livewire.website.blog-list', [
            'featuredPost' => $featuredPost,
            'posts' => $posts,
            'categories' => $categories,
        ])->layout('layouts.website', ['title' => 'Blog — Ecommet']);
    }
}
