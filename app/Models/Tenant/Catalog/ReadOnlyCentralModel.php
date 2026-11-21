<?php

namespace App\Models\Tenant\Catalog;

use App\Support\Catalog\CentralTable;
use App\Support\Catalog\CentralWriteAttempted;
use App\Support\Catalog\ReadOnlyQueryBuilder;

/**
 * Read-only Eloquent access to a CENTRAL table through the TENANT connection (dedup plan §3.2), so central rows
 * can be joined / eager-loaded together with tenant tables in one query.
 *
 * Use on a model that sets `protected string $centralTable = 'products';`. The table resolves to
 * "<central db>.<table>" from config (CentralTable), never a literal. Every write path throws
 * CentralWriteAttempted: save / create / update / delete / touch / increment on the model, and every write on its
 * Eloquent or base query builder (ReadOnlyQueryBuilder).
 *
 * @property-read string $centralTable
 */
trait ReadOnlyCentralModel
{
    public function initializeReadOnlyCentralModel(): void
    {
        // Tenant connection (unless the model names another), so joins with tenant tables run as one query.
        if ($this->connection === null) {
            $this->setConnection('tenant');
        }
    }

    public function getTable(): string
    {
        return CentralTable::qualified($this->centralTable);
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new ReadOnlyQueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
    }

    public function save(array $options = [])
    {
        throw CentralWriteAttempted::on($this->getTable(), 'save');
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw CentralWriteAttempted::on($this->getTable(), 'update');
    }

    public function delete()
    {
        throw CentralWriteAttempted::on($this->getTable(), 'delete');
    }

    public function forceDelete()
    {
        throw CentralWriteAttempted::on($this->getTable(), 'forceDelete');
    }

    public function touch($attribute = null)
    {
        throw CentralWriteAttempted::on($this->getTable(), 'touch');
    }

    protected function increment($column, $amount = 1, array $extra = [])
    {
        throw CentralWriteAttempted::on($this->getTable(), 'increment');
    }

    protected function decrement($column, $amount = 1, array $extra = [])
    {
        throw CentralWriteAttempted::on($this->getTable(), 'decrement');
    }
}
