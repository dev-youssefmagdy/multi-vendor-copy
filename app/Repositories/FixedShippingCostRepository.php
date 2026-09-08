<?php

namespace App\Repositories;

use App\Models\FixedShippingCost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FixedShippingCostRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return FixedShippingCost::query()
            ->with('country')
            ->when($filters['country_search'] ?? null, function ($q, $v) {
                $q->where(function ($q) use ($v) {
                    $q->where('country_id', $v)
                        ->orWhereHas('country', function ($q) use ($v) {
                            $q->where('iso2', $v)
                                ->orWhere('iso3', $v)
                                ->orWhere('language_code', $v)
                                ->orWhere('phone_code', $v);
                        });
                });
            })
            ->when($filters['active_only'] ?? false, fn($q) => $q->active())
            ->orderBy('country_id')
            ->paginate(25);
    }

    public function stats(): array
    {
        return [
            'total' => FixedShippingCost::count(),
            'active' => FixedShippingCost::active()->count(),
            'countries' => FixedShippingCost::active()->distinct('country_id')->count('country_id'),
        ];
    }

    /**
     * Find the single active FixedShippingCost record for a country.
     */
    public function forCountry(int $countryId): ?FixedShippingCost
    {
        return FixedShippingCost::query()
            ->active()
            ->where('country_id', $countryId)
            ->first();
    }

    /**
     * Return all active FixedShippingCost records with their country.
     * Used to display the per-country rate table on a product page.
     */
    public function allActive(): Collection
    {
        return FixedShippingCost::query()
            ->with('country')
            ->active()
            ->orderBy('country_id')
            ->get();
    }
}
