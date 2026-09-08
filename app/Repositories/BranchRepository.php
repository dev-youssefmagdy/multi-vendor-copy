<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BranchRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Branch::query()
            ->withCount(['products', 'shippingZones'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function (Builder $query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        $branches = Branch::query()->get();

        return [
            'total' => $branches->count(),
            'active' => $branches->where('is_active', true)->count(),
            'default' => $branches->where('is_default', true)->count(),
        ];
    }

    public function findForEditor(Branch $branch): Branch
    {
        return Branch::query()->with(['shippingZones.country', 'shippingZones.rates'])->findOrFail($branch->getKey());
    }
}
