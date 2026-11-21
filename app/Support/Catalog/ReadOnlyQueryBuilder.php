<?php

namespace App\Support\Catalog;

use Illuminate\Database\Query\Builder;

/**
 * Query builder for read-only central models: every write entry point throws, so neither the model,
 * its Eloquent builder (`->update()`, `->delete()`, `upsert`...) nor `toBase()` can write to central.
 */
class ReadOnlyQueryBuilder extends Builder
{
    public function insert(array $values)
    {
        $this->deny('insert');
    }

    public function insertOrIgnore(array $values)
    {
        $this->deny('insertOrIgnore');
    }

    public function insertOrIgnoreReturning(array $values, array $returning = ['*'], array|string|null $uniqueBy = null)
    {
        $this->deny('insertOrIgnoreReturning');
    }

    public function insertGetId(array $values, $sequence = null)
    {
        $this->deny('insertGetId');
    }

    public function insertUsing(array $columns, $query)
    {
        $this->deny('insertUsing');
    }

    public function insertOrIgnoreUsing(array $columns, $query)
    {
        $this->deny('insertOrIgnoreUsing');
    }

    public function update(array $values)
    {
        $this->deny('update');
    }

    public function updateFrom(array $values)
    {
        $this->deny('updateFrom');
    }

    public function updateOrInsert(array $attributes, array|callable $values = [])
    {
        $this->deny('updateOrInsert');
    }

    public function upsert(array $values, array|string $uniqueBy, ?array $update = null)
    {
        $this->deny('upsert');
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $this->deny('increment');
    }

    public function incrementEach(array $columns, array $extra = [])
    {
        $this->deny('incrementEach');
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        $this->deny('decrement');
    }

    public function decrementEach(array $columns, array $extra = [])
    {
        $this->deny('decrementEach');
    }

    public function delete($id = null)
    {
        $this->deny('delete');
    }

    public function truncate()
    {
        $this->deny('truncate');
    }

    private function deny(string $operation): never
    {
        throw CentralWriteAttempted::on(is_string($this->from) ? $this->from : 'central', $operation);
    }
}
