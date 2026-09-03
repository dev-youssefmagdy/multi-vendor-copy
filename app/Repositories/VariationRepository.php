<?php

namespace App\Repositories;

use App\Enums\VariationStatus;
use App\Models\Variation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VariationRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->withCount(['options', 'products'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->orWhereHas('translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->where('field', 'name')->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Variation::query()->count(),
            'active' => Variation::query()->where('status', VariationStatus::Active->value)->count(),
            'options' => Variation::query()->withCount('options')->get()->sum('options_count'),
        ];
    }

    public function findForEditor(Variation $variation): Variation
    {
        return $this->baseQuery()->with('options.translations.language')->findOrFail($variation->getKey());
    }

    protected function baseQuery(): Builder
    {
        return Variation::query()->with('translations.language');
    }
}
