<?php

namespace App\Observers;

use App\Repositories\Tenant\Catalog\ConfigCatalog;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * Dedup plan phase 6 (invariant 8): tenants on `catalog_source.<config section> = central` read central currencies,
 * languages, settings, gateways, templates, email templates and pages live, so every central write of those bumps the
 * global `CentralConfig` version (the per-tenant sync fan-out still runs for tenants on `sync`).
 */
class CentralConfigObserver
{
    public function saved(Model $model): void
    {
        CacheVersion::bumpMany([ConfigCatalog::CACHE_TAG]);
    }

    public function deleted(Model $model): void
    {
        CacheVersion::bumpMany([ConfigCatalog::CACHE_TAG]);
    }

    public function restored(Model $model): void
    {
        CacheVersion::bumpMany([ConfigCatalog::CACHE_TAG]);
    }
}
