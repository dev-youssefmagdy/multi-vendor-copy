<?php

namespace App\Repositories;

use App\Models\Catalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CatalogRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Catalog::query()
            ->withCount(['categories', 'tenants'])
            ->with('translations.language')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('slug', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('translations', fn(Builder $translationQuery) => $translationQuery->where('field', 'name')->where('value', 'like', "%{$search}%"));
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Catalog::query()->count(),
            'active' => Catalog::query()->where('status', 'active')->count(),
            'linked_tenants' => Catalog::query()->whereHas('tenants')->count(),
        ];
    }

    public function findForEditor(Catalog $catalog): Catalog
    {
        return Catalog::query()->with(['translations.language', 'categories', 'tenants'])->findOrFail($catalog->getKey());
    }
}
