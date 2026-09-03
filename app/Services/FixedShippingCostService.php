<?php

namespace App\Services;

use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\FixedShippingCost;
use App\Repositories\FixedShippingCostRepository;

class FixedShippingCostService
{
    public function __construct(
        private readonly FixedShippingCostRepository $repository,
    ) {
    }

    /**
     * Persist a new or updated FixedShippingCost record.
     * Enforces one record per country by upserting on country_id.
     */
    public function save(array $data, ?FixedShippingCost $existing = null): FixedShippingCost
    {
        $record = $existing ?? new FixedShippingCost();

        $record->fill([
            'country_id' => (int) $data['country_id'],
            'price_per_gram' => (float) $data['price_per_gram'],
            'currency_code' => strtoupper(trim((string) ($data['currency_code'] ?? 'USD'))),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $record->save();

        SyncProductFixedShippingCosts::dispatch();

        return $record;
    }

    /**
     * Resolve the active rule for a country.
     */
    public function forCountry(int $countryId): ?FixedShippingCost
    {
        return $this->repository->forCountry($countryId);
    }
}
