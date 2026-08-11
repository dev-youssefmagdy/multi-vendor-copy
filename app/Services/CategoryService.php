<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Translation;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        protected CentralCatalogTenantSyncService $tenantSyncService,
        protected CategoryMediaService $mediaService,
    ) {
    }

    public function save(array $attributes, ?Category $category = null): Category
    {
        $category = DB::transaction(function () use ($attributes, $category) {
            $category ??= new Category();

            // Build per-locale slugs: auto-generate from locale name if slug field is empty
            $translations = $attributes['translations'] ?? [];
            foreach ($translations as $locale => &$localeData) {
                $localeName = trim((string) data_get($localeData, 'name', ''));
                $localeSlugInput = trim((string) data_get($localeData, 'slug', ''));
                $generated = Str::slug($localeSlugInput ?: $localeName);
                $localeData['slug'] = $this->uniqueSlug($generated, $locale, $category->getKey());
            }
            unset($localeData);

            $category->fill([
                'parent_id' => $attributes['parent_id'] ?? null,
                'status' => $attributes['status'],
                'is_featured' => (bool) ($attributes['is_featured'] ?? false),
            ]);

            $category->save();
            $category->syncTranslations($translations);

            if (($attributes['remove_thumb'] ?? false) === true) {
                $this->mediaService->purgeThumb($category);
            }

            if (($attributes['thumb_image'] ?? null) instanceof UploadedFile) {
                $this->mediaService->syncThumb($category, $attributes['thumb_image']);
            }

            return $category->fresh(['translations.language', 'parent.translations.language', 'files']);
        });

        $this->tenantSyncService->syncAllTenants(['categories', 'products']);

        return $category;
    }

    protected function uniqueSlug(string $base, string $locale, mixed $ignoreId = null): string
    {
        $base = $base ?: Str::random(8);
        $slug = $base;
        $index = 1;

        while (
            Translation::query()
                ->where('translatable_type', Category::class)
                ->when($ignoreId, fn($q) => $q->where('translatable_id', '!=', $ignoreId))
                ->whereHas('language', fn($q) => $q->where('code', $locale))
                ->where('field', 'slug')
                ->where('value', $slug)
                ->exists()
        ) {
            $index++;
            $slug = sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }
}
