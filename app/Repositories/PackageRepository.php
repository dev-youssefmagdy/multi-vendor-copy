<?php

namespace App\Repositories;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PackageRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->withCount('tenants')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhereHas('translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->whereIn('field', ['name', 'description'])->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))

            ->paginate($perPage);
    }

    public function stats(): array
    {
        $packages = Package::query()->withCount('tenants')->get();

        return [
            'total' => $packages->count(),
            'published' => $packages->where('status', PackageStatus::Published)->count(),
            'tenants' => $packages->sum('tenants_count'),
        ];
    }

    public function findForEditor(Package $package): Package
    {
        return $this->baseQuery()->findOrFail($package->getKey());
    }

    protected function baseQuery(): Builder
    {
        return Package::query()->with('translations.language');
    }
}
