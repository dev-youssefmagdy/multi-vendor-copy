<?php

namespace App\Repositories;

use App\Enums\DomainRequestStatus;
use App\Models\DomainRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DomainRequestRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return DomainRequest::query()
            ->with('tenant')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('domain', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn(Builder $tenantQuery) => $tenantQuery->where('name', 'like', "%{$search}%"));
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('requested_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => DomainRequest::query()->count(),
            'pending' => DomainRequest::query()->where('status', DomainRequestStatus::Pending->value)->count(),
            'connected' => DomainRequest::query()->where('status', DomainRequestStatus::Connected->value)->count(),
        ];
    }

    public function tenantOptions(): array
    {
        return \App\Models\Tenant::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
