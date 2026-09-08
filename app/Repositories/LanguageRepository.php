<?php

namespace App\Repositories;

use App\Enums\LanguageDirection;
use App\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LanguageRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Language::query()
            ->with('imageFile')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('native_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['direction'] ?? null), fn($query) => $query->where('direction', $filters['direction']))
            ->when(($filters['is_active'] ?? '') !== '', fn($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy('sort_order')
            ->orderByDesc('is_default')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Language::query()->count(),
            'active' => Language::query()->where('is_active', true)->count(),
            'rtl' => Language::query()->where('direction', LanguageDirection::Rtl->value)->count(),
        ];
    }
}
