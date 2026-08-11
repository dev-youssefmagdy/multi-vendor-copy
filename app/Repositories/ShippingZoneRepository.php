<?php

namespace App\Repositories;

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShippingZoneRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->withCount(['rates', 'products'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
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
        $zones = ShippingZone::query()->withCount('rates')->get();

        return [
            'total' => $zones->count(),
            'active' => $zones->where('status', ShippingZoneStatus::Active)->count(),
            'rates' => $zones->sum('rates_count'),
        ];
    }

    public function findForEditor(ShippingZone $shippingZone): ShippingZone
    {
        return $this->baseQuery()->with('rates')->findOrFail($shippingZone->getKey());
    }

    protected function baseQuery(): Builder
    {
        return ShippingZone::query()->with(['translations.language', 'branch', 'country']);
    }
}
