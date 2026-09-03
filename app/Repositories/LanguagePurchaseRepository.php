<?php

namespace App\Repositories;

use App\Models\TenantLanguagePurchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LanguagePurchaseRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = TenantLanguagePurchase::query()->with(['language', 'tenant']);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('transaction_uuid', 'like', "%{$search}%")
                    ->orWhere('gateway_code', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('language', fn($l) => $l->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if (filled($filters['gateway'] ?? null)) {
            $query->where('gateway_code', $filters['gateway']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function stats(): array
    {
        $query = TenantLanguagePurchase::query();

        return [
            'total' => $query->count(),
            'revenue' => (float) $query->sum('amount'),
        ];
    }

    public function find(int $id): ?TenantLanguagePurchase
    {
        return TenantLanguagePurchase::query()->with(['language', 'tenant'])->find($id);
    }
}
