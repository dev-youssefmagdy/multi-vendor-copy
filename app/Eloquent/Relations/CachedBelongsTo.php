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
    /** [relatedClass => [ownerKeyValue => Model]] */
    private static array $cache = [];

    /** Keys requested for the current eager-load batch. */
    private array $batchKeys = [];

    public static function flushCache(): void
    {
        static::$cache = [];
    }

    public function addEagerConstraints(array $models): void
    {
        $class = \get_class($this->related);
        $allKeys = $this->getEagerModelKeys($models);
        $this->batchKeys = $allKeys;

        // Only query the DB for IDs that aren't cached yet.
        $missing = array_values(
            array_filter($allKeys, fn($k) => $k !== null && !isset(static::$cache[$class][$k]))
        );

        if (empty($missing)) {
            // Force an empty result — all IDs are already in the identity map.
            $this->query->whereRaw('1 = 0');
            return;
        }

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);
        $this->whereInEager($whereIn, $this->getQualifiedOwnerKeyName(), $missing);
    }

    public function getEager(): Collection
    {
        $class = \get_class($this->related);

        // Store any freshly loaded models in the identity map.
        foreach (parent::getEager() as $model) {
            static::$cache[$class][$model->{$this->ownerKey}] = $model;
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
