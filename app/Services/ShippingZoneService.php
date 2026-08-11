<?php

namespace App\Services;

use App\Models\Language;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShippingZoneService
{
    public function save(array $attributes, ?ShippingZone $shippingZone = null): ShippingZone
    {
        return DB::transaction(function () use ($attributes, $shippingZone) {
            $shippingZone ??= new ShippingZone();
            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? array_key_first($attributes['translations'] ?? []) ?? 'en';
            $name = trim((string) data_get($attributes, "translations.{$defaultLocale}.name", $attributes['name'] ?? ''));

            $shippingZone->fill([
                'name'          => $name,
                'code'          => Str::slug($attributes['code'] ?? $name, '-'),
                'currency_code' => strtoupper(trim($attributes['currency_code'] ?? 'USD')),
                'status'        => $attributes['status'],
            ]);
            $shippingZone->save();
            $shippingZone->syncTranslations($attributes['translations'] ?? []);

            $existingIds = [];

            foreach ($attributes['rates'] ?? [] as $index => $rate) {
                $zoneRate = isset($rate['id'])
                    ? $shippingZone->rates()->find($rate['id'])
                    : $shippingZone->rates()->make();

                if (! $zoneRate) {
                    $zoneRate = $shippingZone->rates()->make();
                }

                $zoneRate->fill([
                    'name'       => $rate['name'],
                    'min_weight' => ($rate['min_weight'] ?? '') === '' ? null : $rate['min_weight'],
                    'max_weight' => ($rate['max_weight'] ?? '') === '' ? null : $rate['max_weight'],
                    'price'      => $rate['price'] ?? 0,
                    'is_active'  => (bool) ($rate['is_active'] ?? true),
                ]);
                $zoneRate->save();
                $existingIds[] = $zoneRate->id;
            }

            $shippingZone->rates()->whereNotIn('id', $existingIds)->get()->each->delete();

            return $shippingZone->fresh(['translations.language', 'rates']);
        });
    }
}
