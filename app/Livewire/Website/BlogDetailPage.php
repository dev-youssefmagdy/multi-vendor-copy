<?php

namespace App\Livewire\Website;

use App\Enums\ContentStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Component;

class BlogDetailPage extends Component
{
    public BlogPost $post;

    public function mount(string $slug): void
    {
        $this->post = BlogPost::query()
            ->with(['translations.language', 'category.translations.language'])
            ->where('status', ContentStatus::Published->value)
            ->whereNotNull('published_at')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhereHas('translations', fn($tq) => $tq
                        ->where('field', 'slug')
                        ->where('value', $slug));
            })
            ->firstOrFail();
    }

    public function render()
    {
        $related = BlogPost::query()
            ->with(['translations.language', 'category.translations.language'])
            ->where('status', ContentStatus::Published->value)
            ->whereNotNull('published_at')
            ->whereKeyNot($this->post->id)
            ->when($this->post->blog_category_id, fn($query) => $query->where('blog_category_id', $this->post->blog_category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $categories = BlogCategory::query()
            ->with('translations.language')
            ->whereIn('status', [ContentStatus::Active->value, ContentStatus::Published->value])
            ->withCount(['posts' => fn($query) => $query->where('status', ContentStatus::Published->value)])
            ->get()
            ->sortBy('name')
            ->values();

        return view('livewire.website.blog-detail', [
            'post' => $this->post,
            'related' => $related,
            'categories' => $categories,
        ])->layout('layouts.website', ['title' => $this->post->title . ' — Blog — Ecommet']);
    }
}
