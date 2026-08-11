<?php

namespace App\Repositories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TenantRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->with(['package.translations.language', 'primaryLanguage', 'domains'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.name')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.email')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.phone')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.slug')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query/*->where('status', $filters['status'])*/ ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) = ?", [$filters['status']]))
            ->when(filled($filters['catalog_id'] ?? null), fn($query) => $query->whereJsonContains('category_ids', (int) $filters['catalog_id']))
            ->when(filled($filters['package_id'] ?? null), fn($query) => $query->where('package_id', $filters['package_id'])->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.package_id')) = ?", [$filters['package_id']]))
            ->latest('updated_at');

        // `logo` is a computed accessor (per-tenant setting), not a queryable column,
        // so "has image / no image" is applied in-memory after loading the matching set.
        if (filled($filters['has_image'] ?? null)) {
            $wantsImage = $filters['has_image'] === 'yes';
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;

            $filtered = $query->get()->filter(fn(Tenant $tenant) => $wantsImage ? filled($tenant->logo) : blank($tenant->logo))->values();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->forPage($page, $perPage),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );
        }

        return $query->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Tenant::query()->count(),
            'active' => Tenant::query()->where('status', TenantStatus::Active->value)->count(),
            'onboarding' => Tenant::query()->where('status', TenantStatus::Onboarding->value)->count(),
        ];
    }

    public function findForEditor(Tenant $tenant): Tenant
    {
        return Tenant::query()->with(['package.translations.language', 'primaryLanguage', 'domains'])->findOrFail($tenant->getKey());
    }
}
