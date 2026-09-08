<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogCategoryService
{
    public function save(array $attributes, ?BlogCategory $category = null): BlogCategory
    {
        return DB::transaction(function () use ($attributes, $category) {
            $category ??= new BlogCategory();

            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
            $defaultName = $attributes['translations'][$defaultLocale]['name'] ?? '';

            // Build per-locale translations including auto-generated slug
            $translationsToSync = collect($attributes['translations'] ?? [])
                ->map(fn($fields, $locale) => [
                    'name' => $fields['name'] ?? '',
                    'slug' => filled($fields['slug'] ?? '')
                        ? Str::slug($fields['slug'])
                        : Str::slug($fields['name'] ?? ''),
                ])
                ->all();

            // Canonical DB slug comes from the default locale
            $canonicalSlug = $translationsToSync[$defaultLocale]['slug']
                ?: Str::slug($defaultName);

            $category->fill([
                'slug' => $canonicalSlug,
                'status' => $attributes['status'],
            ]);
            $category->save();

            $category->syncTranslations($translationsToSync);

            return $category->fresh('translations.language');
        });
    }

    public function delete(BlogCategory $category): void
    {
        $category->delete();
    }
}
