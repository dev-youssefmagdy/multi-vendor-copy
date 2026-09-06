<?php

namespace App\Observers;

use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * Bumps the model's cache-version tag on any write so storefront caches keyed
 * on that tag (see StorefrontRepository::cacheRemember) invalidate immediately.
 */
class CacheVersionObserver
{
    public function saved(Model $model): void
    {
        CacheVersion::bump(class_basename($model));
    }

    public function deleted(Model $model): void
    {
        CacheVersion::bump(class_basename($model));
    }

    public function restored(Model $model): void
    {
        CacheVersion::bump(class_basename($model));
    }
}
