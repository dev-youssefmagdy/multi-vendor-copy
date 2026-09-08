<?php

namespace App\Repositories;

use App\Models\DnsRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DnsRecordRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return DnsRecord::query()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            })
            ->when(filled($filters['type'] ?? null), fn($q) => $q->where('type', $filters['type']))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allRequired(): \Illuminate\Database\Eloquent\Collection
    {
        return DnsRecord::query()->where('is_required', true)->get();
    }

    public function stats(): array
    {
        return [
            'total'    => DnsRecord::query()->count(),
            'required' => DnsRecord::query()->where('is_required', true)->count(),
        ];
    }
}
