<?php

namespace App\Repositories;

use App\Enums\ContentStatus;
use App\Models\StaticPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaticPageRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return StaticPage::query()
            ->with('translations.language')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn($q) => $q
                        ->whereIn('field', ['title', 'slug'])
                        ->where('value', 'like', "%{$search}%"));
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => StaticPage::query()->count(),
            'active' => StaticPage::query()->where('status', ContentStatus::Active->value)->count(),
            'drafts' => StaticPage::query()->where('status', ContentStatus::Draft->value)->count(),
        ];
    }
}
