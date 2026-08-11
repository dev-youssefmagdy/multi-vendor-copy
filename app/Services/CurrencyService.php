<?php

namespace App\Services;

use App\Enums\AppSettingType;
use App\Models\AppSetting;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CurrencyService
{
    public function save(array $attributes, ?Currency $currency = null): Currency
    {
        return DB::transaction(function () use ($attributes, $currency) {
            $currency ??= new Currency();
            $currency->fill([
                'code' => strtoupper(trim((string) $attributes['code'])),
                'name' => trim((string) $attributes['name']),
                'sign' => filled($attributes['sign'] ?? null) ? trim((string) $attributes['sign']) : null,
                'conversion_rate' => $attributes['conversion_rate'],
            ]);
            $currency->save();

            if ((bool) ($attributes['is_default'] ?? false)) {
                AppSetting::query()->updateOrCreate(
                    ['key' => 'default_currency'],
                    ['value' => $currency->code, 'type' => AppSettingType::String->value, 'is_public' => true]
                );
            }

            return $currency->fresh();
        });
    }
}
