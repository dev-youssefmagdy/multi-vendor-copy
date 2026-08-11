<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariationService
{
    public function __construct(protected CentralCatalogTenantSyncService $tenantSyncService)
    {
    }

    public function save(array $attributes, ?Variation $variation = null): Variation
    {
        $catalogIds = [];

        $variation = DB::transaction(function () use ($attributes, $variation, &$catalogIds) {
            $variation ??= new Variation();
            $catalogIds = $variation->exists ? $this->tenantSyncService->catalogIdsForVariation($variation) : [];
            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? array_key_first($attributes['translations'] ?? []) ?? 'en';
            $name = trim((string) data_get($attributes, "translations.{$defaultLocale}.name", ''));

            $variation->fill([
                // 'slug' => $this->uniqueVariationSlug($name, $variation->getKey()),
                'status' => $attributes['status'],
            ]);

            $variation->save();
            $variation->syncTranslations($attributes['translations'] ?? []);

            $existingIds = [];

            foreach ($attributes['options'] ?? [] as $index => $optionAttributes) {
                $option = isset($optionAttributes['id'])
                    ? VariationOption::query()->where('variation_id', $variation->id)->find($optionAttributes['id'])
                    : new VariationOption();

                if (!$option) {
                    $option = new VariationOption();
                }

                $optionName = trim((string) data_get($optionAttributes, "translations.{$defaultLocale}.name", ''));

                $option->fill([
                    'variation_id' => $variation->id,
                    'slug' => $this->uniqueOptionSlug($variation, $optionName, $option->getKey()),
                    'swatch' => $optionAttributes['swatch'] ?? null,
                ]);
                $option->save();
                $option->syncTranslations($optionAttributes['translations'] ?? []);
                $existingIds[] = $option->id;
            }

            $variation->options()->whereNotIn('id', $existingIds)->get()->each->delete();

            return $variation->fresh(['translations.language', 'options.translations.language']);
        });

        $this->tenantSyncService->syncCatalogs($catalogIds, ['products']);

        return $variation;
    }

    protected function uniqueVariationSlug(?string $value, mixed $ignoreId = null): string|null
    {
        return null;
        $base = Str::slug((string) $value) ?: Str::random(8);
        $slug = $base;
        $index = 1;

        while (Variation::query()->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->where('slug', $slug)->exists()) {
            $index++;
            $slug = \sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }

    protected function uniqueOptionSlug(Variation $variation, ?string $value, mixed $ignoreId = null): string|null
    {
        return null;
        $base = Str::slug((string) $value) ?: Str::random(6);
        $slug = $base;
        $index = 1;

        while ($variation->options()->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->where('slug', $slug)->exists()) {
            $index++;
            $slug = \sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }
}
