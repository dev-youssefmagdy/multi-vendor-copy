<?php

namespace App\Repositories;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PackageRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->addSelect([
                // package_id moved into tenants.data JSON (migration 2026_08_18_000001)
                'tenants_count' => DB::table('tenants')
                    ->selectRaw('count(*)')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(tenants.data, '$.package_id')) = CAST(packages.id AS CHAR)"),
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhereHas('translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->whereIn('field', ['name', 'description'])->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->paginate($perPage);
    }

    public function stats(): array
    {
        $packages  = Package::query()->get();
        $total     = $packages->count();
        $published = $packages->where('status', PackageStatus::Published)->count();

        // package_id moved into tenants.data JSON (migration 2026_08_18_000001)
        $tenantCount = DB::table('tenants')
            ->whereRaw("JSON_EXTRACT(data, '$.package_id') IS NOT NULL")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.package_id')) != 'null'")
            ->count();

        return [
            'total'     => $total,
            'published' => $published,
            'tenants'   => $tenantCount,
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
