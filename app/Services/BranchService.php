<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ShippingZone;
use App\Enums\ShippingZoneStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BranchService
{
    public function save(array $attributes, ?Branch $branch = null): Branch
    {
        return DB::transaction(function () use ($attributes, $branch) {
            $isNew = $branch === null;
            $branch ??= new Branch();

            $isDefault = (bool) ($attributes['is_default'] ?? false);

            // Only one branch can be default
            if ($isDefault) {
                Branch::query()->where('id', '!=', $branch->id ?? 0)->update(['is_default' => false]);
            }

            $branch->fill([
                'name' => trim($attributes['name']),
                'code' => Str::slug($attributes['code'] ?? $attributes['name'], '-'),
                'phone' => $attributes['phone'] ?: null,
                'email' => $attributes['email'] ?: null,
                'address' => $attributes['address'] ?: null,
                'city' => $attributes['city'] ?: null,
                'country' => $attributes['country'] ?: null,
                'is_default' => $isDefault,
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'default_free_shipping_weight' => (int) ($attributes['default_free_shipping_weight'] ?? 1500),
            ]);
            $branch->save();

            // Sync shipping zones
            $this->syncShippingZones($branch, $attributes['shipping_zones'] ?? []);

            return $branch->fresh(['shippingZones.country', 'shippingZones.rates']);
        });
    }

    protected function syncShippingZones(Branch $branch, array $zonesData): void
    {
        $existingIds = [];

        foreach ($zonesData as $zoneData) {
            if (isset($zoneData['id'])) {
                $zone = ShippingZone::query()->where('branch_id', $branch->id)->find($zoneData['id']);
            } else {
                $zone = new ShippingZone();
            }

            $zone->fill([
                'branch_id' => $branch->id,
                'country_id' => $zoneData['country_id'] ?: null,
                'name' => $zoneData['name'],
                'code' => Str::slug($zoneData['code'] ?? $zoneData['name'], '-'),
                'currency_code' => strtoupper(trim($zoneData['currency_code'] ?? 'USD')),
                'status' => $zoneData['status'] ?? ShippingZoneStatus::Active->value,
            ]);
            $zone->save();

            $existingRateIds = [];

            foreach ($zoneData['rates'] ?? [] as $rateData) {
                $rate = isset($rateData['id'])
                    ? $zone->rates()->find($rateData['id'])
                    : $zone->rates()->make();

                $rate ??= $zone->rates()->make();

                $rate->fill([
                    'name' => $rateData['name'],
                    'min_weight' => ($rateData['min_weight'] ?? '') === '' ? null : $rateData['min_weight'],
                    'max_weight' => ($rateData['max_weight'] ?? '') === '' ? null : $rateData['max_weight'],
                    'price' => $rateData['price'] ?? 0,
                    'is_active' => (bool) ($rateData['is_active'] ?? true),
                ]);
                $rate->save();
                $existingRateIds[] = $rate->id;
            }

            $zone->rates()->whereNotIn('id', $existingRateIds)->delete();
            $existingIds[] = $zone->id;
        }


        // Remove zones that were deleted in the form
        $branch->shippingZones()->whereNotIn('id', $existingIds)->each(function (ShippingZone $zone) {
            $zone->rates()->delete();
            $zone->delete();
        });
    }

    public function delete(Branch $branch): void
    {
        DB::transaction(function () use ($branch) {
            $branch->shippingZones()->each(function (ShippingZone $zone) {
                $zone->rates()->delete();
                $zone->delete();
            });
            $branch->branchProducts()->delete();
            $branch->delete();
        });
    }
}
