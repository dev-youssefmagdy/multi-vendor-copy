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
                'products_limit' => (int) ($attributes['products_limit'] ?? -1),
                'banners_limit' => (int) ($attributes['banners_limit'] ?? -1),
                'languages_limit' => (int) ($attributes['languages_limit'] ?? -1),
                'orders_per_month_limit' => (int) ($attributes['orders_per_month_limit'] ?? -1),
                'ai_calls_limit' => (int) ($attributes['ai_calls_limit'] ?? -1),
                'image_searches_limit' => (int) ($attributes['image_searches_limit'] ?? -1),
                'trial_days' => $attributes['trial_days'] ?? 0,
            ]);
            $package->save();
            $package->syncTranslations($attributes['translations'] ?? []);

            return $package->fresh(['translations.language']);
        });
    }


}
