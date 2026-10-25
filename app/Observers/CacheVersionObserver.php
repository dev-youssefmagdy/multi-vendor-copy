<?php

namespace App\Observers;

use App\Models\Category as CentralCategory;
use App\Models\Country;
use App\Models\Tenant\File as TenantFile;
use App\Models\Tenant\FlashSale as TenantFlashSale;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ThemeCountry;
use App\Models\Tenant\Translation as TenantTranslation;
use App\Models\Translation as CentralTranslation;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * Bumps the model's cache-version tag on any write so storefront caches keyed
 * on that tag (see StorefrontRepository::cacheRemember) invalidate immediately.
 * CacheVersion::bumpMany() defers the write until the surrounding transaction
 * commits, so translations/pivots written in the same transaction are visible
 * before the new version is.
 */
class CacheVersionObserver
{
    public function saved(Model $model): void
    {
        CacheVersion::bumpMany(self::tagsFor($model));
    }

    public function deleted(Model $model): void
    {
        CacheVersion::bumpMany(self::tagsFor($model));
    }

    public function restored(Model $model): void
    {
        CacheVersion::bumpMany(self::tagsFor($model));
    }

    /**
     * Tags a write to the given model invalidates. Child rows (translations,
     * files, theme countries) also bump the tag of the model that owns them,
     * since storefront caches are keyed on the owner.
     *
     * @return string[]
     */
    public static function tagsFor(Model $model): array
    {
        return match (true) {
            $model instanceof TenantTranslation => array_filter(['Translation', self::ownerTag($model->translatable_type)]),
            $model instanceof TenantFile => array_filter(['File', self::ownerTag($model->model_type)]),
            $model instanceof ThemeCountry => ['Theme'],
            // Category lists carry product counts / prune empty leaves.
            $model instanceof TenantProduct => ['Product', 'Category'],
            // Product cards/prices carry the active flash-sale discount.
            $model instanceof TenantFlashSale => ['FlashSale', 'Product'],
            $model instanceof CentralCategory => ['CentralCategory'],
            // Central translations only matter to tenant caches for countries.
            $model instanceof CentralTranslation => self::ownerTag($model->translatable_type) === 'Country' ? ['Country'] : [],
            default => [class_basename($model)],
        };
    }

    /** Tag of a polymorphic owner type (class name or morph alias). */
    public static function ownerTag(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return class_basename(Model::getActualClassNameForMorph($type));
    }
}
