<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogService
{
    public function save(array $attributes, ?Catalog $catalog = null): Catalog
    {
        return DB::transaction(function () use ($attributes, $catalog) {
            $catalog ??= new Catalog();
            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? array_key_first($attributes['translations'] ?? []) ?? 'en';
            $name = trim((string) data_get($attributes, "translations.{$defaultLocale}.name", ''));

            $catalog->fill([
                'slug' => $this->uniqueSlug($attributes['slug'] ?? $name, $catalog->getKey()),
                'status' => $attributes['status'] ?? 'active',
                'name' => $name !== '' ? $name : ($catalog->name ?? Str::headline($catalog->slug ?? 'catalog')),
            ]);

            $catalog->save();
            $catalog->syncTranslations($attributes['translations'] ?? []);

            // Sync category assignments when parent_category_ids is explicitly provided
            if (array_key_exists('parent_category_ids', $attributes)) {
                $parentIds = array_values(array_filter(array_map('intval', (array) $attributes['parent_category_ids'])));
                $allCategoryIds = $this->resolveWithDescendants($parentIds);
                $catalog->categories()->sync($allCategoryIds);
            }

            return $catalog->fresh(['translations.language', 'categories', 'tenants']);
        });
    }

    /**
     * Given a list of parent category IDs, return those IDs plus all their recursive descendants.
     */
    protected function resolveWithDescendants(array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        $all = $parentIds;
        $toExpand = $parentIds;

        while ($toExpand !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $toExpand)
                ->pluck('id')
                ->all();

            $new = array_diff($children, $all);
            $all = array_merge($all, $new);
            $toExpand = $new;
        }

        return array_values(array_unique($all));
    }

    protected function uniqueSlug(?string $value, mixed $ignoreId = null): string
    {
        $base = Str::slug((string) $value) ?: Str::random(8);
        $slug = $base;
        $index = 1;

        while (Catalog::query()->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))->where('slug', $slug)->exists()) {
            $index++;
            $slug = sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }
}
