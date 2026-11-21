<?php

namespace App\Models\Concerns;

use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dual-write of the typed reference pairs (dedup plan phase 1) for Eloquent rows that reference synced catalog
 * entities by tenant id (order_items, favorites, product_rates, flash_sales, central return/edit requests).
 *
 * On save, for every source column (see CatalogRefColumns::specFor()) that is new/changed and has no explicitly set
 * pair, the pair is derived from the tenant row's central link (CatalogRefResolver). Callers that already know the
 * refs (OrderComposer, batch) set the pair themselves and are left alone. Never throws: a failed lookup leaves the
 * pair NULL for `catalog:backfill-refs`. No-op until the migration has added the columns.
 *
 * Central models (rows keyed by `tenant_id`) resolve in that tenant's DB via CatalogRefResolver::forTenant().
 */
trait HasCatalogRefs
{
    public static function bootHasCatalogRefs(): void
    {
        static::saving(function (Model $model) {
            $model->fillCatalogRefs();
        });
    }

    public function fillCatalogRefs(): void
    {
        try {
            $this->applyCatalogRefs();
        } catch (Throwable $e) {
            Log::warning('Catalog typed refs not filled on save (picked up by catalog:backfill-refs).', [
                'model' => static::class,
                'id' => $this->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function applyCatalogRefs(): void
    {
        if (! CatalogRefColumns::ready($this->getConnection(), $this->getTable())) {
            return;
        }

        $todo = [];
        foreach (CatalogRefColumns::specFor($this->getTable()) as $column => [$entity, $typeColumn, $idColumn]) {
            $source = $this->getAttribute($column);
            if ($source === null || (int) $source < 1) {
                continue; // keep the last known ref (e.g. variant FK set NULL keeps its history)
            }
            if ($this->isDirty($typeColumn) || $this->isDirty($idColumn)) {
                continue; // set explicitly by the caller
            }
            if ($this->exists && ! $this->isDirty($column) && $this->getAttribute($typeColumn) !== null) {
                continue;
            }
            $todo[$column] = [$entity, $typeColumn, $idColumn, (int) $source];
        }

        if ($todo === []) {
            return;
        }

        $resolver = $this->catalogRefResolver();
        if ($resolver === null) {
            return;
        }

        foreach ($todo as [$entity, $typeColumn, $idColumn, $source]) {
            $ref = $resolver->one($entity, $source);
            // A changed source that cannot be resolved must not keep the old ref.
            $this->setAttribute($typeColumn, $ref?->source);
            $this->setAttribute($idColumn, $ref?->id);
        }
    }

    protected function catalogRefResolver(): ?CatalogRefResolver
    {
        if (isset(CatalogRefColumns::CENTRAL_TABLES[$this->getTable()])) {
            return CatalogRefResolver::forTenant((string) $this->getAttribute('tenant_id'));
        }

        return CatalogRefResolver::on($this->getConnection());
    }
}
