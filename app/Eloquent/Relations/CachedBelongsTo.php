<?php

namespace App\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsTo with a per-request static identity map.
 *
 * The first eager-load batch that needs a given set of related IDs fires a
 * normal DB query and stores the results. All subsequent batches that ask for
 * the same IDs (same related class) are served entirely from the in-memory
 * cache — no second round-trip to the database.
 *
 * Flush between requests by calling CachedBelongsTo::flushCache() in a
 * terminating middleware or AppServiceProvider::boot().
 */
class CachedBelongsTo extends BelongsTo
{
    /** [scope|relatedClass => [ownerKeyValue => Model]] — scope isolates tenants in reused processes. */
    private static array $cache = [];

    /** Keys requested for the current eager-load batch. */
    private array $batchKeys = [];

    /** True when every requested key was already in the identity map (no query needed). */
    private bool $fullyCached = false;

    public static function flushCache(): void
    {
        static::$cache = [];
    }

    /** Identity-map bucket: related class partitioned by the active tenant (or central). */
    private function bucket(): string
    {
        $tenantId = function_exists('tenant') ? tenant()?->getTenantKey() : null;

        return ($tenantId ?? 'central') . '|' . \get_class($this->related);
    }

    public function addEagerConstraints(array $models): void
    {
        $class = $this->bucket();
        $allKeys = $this->getEagerModelKeys($models);
        $this->batchKeys = $allKeys;

        // Only query the DB for IDs that aren't cached yet.
        $missing = array_values(
            array_filter($allKeys, fn($k) => $k !== null && !isset(static::$cache[$class][$k]))
        );

        $this->fullyCached = empty($missing);

        if ($this->fullyCached) {
            // All IDs are already in the identity map; getEager() skips the query.
            return;
        }

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);
        $this->whereInEager($whereIn, $this->getQualifiedOwnerKeyName(), $missing);
    }

    public function getEager(): Collection
    {
        $class = $this->bucket();

        // Store any freshly loaded models in the identity map.
        if (!$this->fullyCached) {
            foreach (parent::getEager() as $model) {
                static::$cache[$class][$model->{$this->ownerKey}] = $model;
            }
        }

        // Return all requested models from the identity map (deduplicated).
        $seen = [];
        $result = [];
        foreach ($this->batchKeys as $k) {
            if ($k === null || isset($seen[$k])) {
                continue;
            }
            if (isset(static::$cache[$class][$k])) {
                $result[] = static::$cache[$class][$k];
                $seen[$k] = true;
            }
        }

        return new Collection($result);
    }
}
