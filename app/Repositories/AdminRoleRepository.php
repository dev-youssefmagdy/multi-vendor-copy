<?php

namespace App\Repositories;

use App\Models\AdminRole;
use App\Support\AdminPermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminRoleRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return AdminRole::query()
            ->withCount('admins')
            ->when(filled($filters['search'] ?? null), fn($query) => $query->where('name', 'like', '%' . trim((string) $filters['search']) . '%'))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => AdminRole::query()->count(),
            'permissions' => (int) AdminRole::query()->sum('permissions_count'),
            'assigned' => \App\Models\AdminUser::query()->whereNotNull('role_id')->count(),
        ];
    }

    public function availablePermissions(): array
    {
        return AdminPermissions::definitions();
    }
}
