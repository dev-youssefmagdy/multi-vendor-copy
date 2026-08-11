<?php

namespace App\Repositories;

use App\Enums\ContentStatus;
use App\Models\BlogPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogPostRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return BlogPost::query()
            ->with(['category.translations.language', 'translations.language'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn($q) => $q
                        ->whereIn('field', ['title', 'slug'])
                        ->where('value', 'like', "%{$search}%"));
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('published_at')
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => BlogPost::query()->count(),
            'published' => BlogPost::query()->where('status', ContentStatus::Published->value)->count(),
            'drafts' => BlogPost::query()->where('status', ContentStatus::Draft->value)->count(),
        ];
    }

    public function categoryOptions(): array
    {
        return \App\Models\BlogCategory::query()
            ->with('translations.language')
            ->orderBy('slug')
            ->get()->mapWithKeys(fn($category) => [
                $category->id => $category->translationValue('name'),
            ])->all();
    }
}
