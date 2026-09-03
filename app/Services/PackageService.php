<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Package;
use Illuminate\Support\Facades\DB;


class PackageService
{
    public function save(array $attributes, ?Package $package = null): Package
    {
        return DB::transaction(function () use ($attributes, $package) {
            $package ??= new Package();
            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? array_key_first($attributes['translations'] ?? []) ?? 'en';
            $name = trim((string) data_get($attributes, "translations.{$defaultLocale}.name", $attributes['name'] ?? ''));

            $package->fill([
                'name' => $name,
                'status' => $attributes['status'],
                'icon' => $attributes['icon'] ?? null,
                'term' => $attributes['term'],
                'price' => $attributes['price'],
                'features' => blank($attributes['features'] ?? null) ? null : $attributes['features'],
                'categories_count' => (int) ($attributes['categories_count'] ?? 0),
                'trial_days' => $attributes['trial_days'] ?? 0,
            ]);
            $package->save();
            $package->syncTranslations($attributes['translations'] ?? []);

            return $package->fresh(['translations.language']);
        });
    }


}
