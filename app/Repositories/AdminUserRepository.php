<?php

namespace App\Repositories;

use App\Enums\ActivationStatus;
use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return AdminUser::query()
            ->with('role')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => AdminUser::query()->count(),
            'active' => AdminUser::query()->where('status', ActivationStatus::Active->value)->count(),
            'roles' => \App\Models\AdminRole::query()->count(),
        ];
    }

    public function roleOptions(): array
    {
        return \App\Models\AdminRole::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
