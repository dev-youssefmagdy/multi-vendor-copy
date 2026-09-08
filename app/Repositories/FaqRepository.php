<?php

namespace App\Repositories;

use App\Enums\ContentStatus;
use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaqRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Faq::query()
            ->with('translations.language')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->whereHas('translations', fn($q) => $q->whereIn('field', ['question', 'category'])->where('value', 'like', "%{$search}%"));
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Faq::query()->count(),
            'active' => Faq::query()->where('status', ContentStatus::Active->value)->count(),
            'categories' => \App\Models\Translation::query()
                ->where('translatable_type', Faq::class)
                ->where('field', 'category')
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->distinct('value')
                ->count('value'),
        ];
    }
}
