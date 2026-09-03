<?php

namespace App\Repositories;

use App\Enums\ContentStatus;
use App\Models\BlogCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogCategoryRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return BlogCategory::query()
            ->with('translations.language')
            ->withCount('posts')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn($q) => $q
                        ->whereIn('field', ['name', 'slug'])
                        ->where('value', 'like', "%{$search}%"));
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => BlogCategory::query()->count(),
            'active' => BlogCategory::query()->where('status', ContentStatus::Active->value)->count(),
            'posts' => \App\Models\BlogPost::query()->count(),
        ];
    }
}
