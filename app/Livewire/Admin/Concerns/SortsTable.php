<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

/**
 * PANELS-UPDATES-TABLE-SORTING: click-to-sort column headers for central Livewire tables.
 *
 * A page maps its header labels to sort keys in sortableColumns() (label => key); clicking a header
 * calls sortByColumn(key) which toggles asc/desc, and the page applies the order with applySort() /
 * the key + direction it hands to its repository. Unknown keys are ignored (whitelist).
 */
trait SortsTable
{
    public string $sortKey = '';

    public string $sortDir = 'desc';

    /** @return array<string, string> header label => sort key */
    protected function sortableColumns(): array
    {
        return [];
    }

    public function sortByColumn(string $key): void
    {
        if (! in_array($key, $this->sortableColumns(), true)) {
            return;
        }

        $this->sortDir = ($this->sortKey === $key && $this->sortDir === 'asc') ? 'desc' : 'asc';
        $this->sortKey = $key;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** The active sort key, only when it is one of this page's sortable columns. */
    protected function activeSortKey(): ?string
    {
        return in_array($this->sortKey, $this->sortableColumns(), true) ? $this->sortKey : null;
    }

    /**
     * Applies the chosen order to an Eloquent/query builder. $columns maps sort key => column (or a closure
     * receiving ($query, $direction)); returns true when a user-chosen order was applied.
     *
     * @param  array<string, string|\Closure>  $columns
     */
    protected function applySort($query, array $columns): bool
    {
        $key = $this->activeSortKey();

        if ($key === null || ! isset($columns[$key])) {
            return false;
        }

        $direction = $this->sortDir === 'asc' ? 'asc' : 'desc';
        $column = $columns[$key];

        $column instanceof \Closure ? $column($query, $direction) : $query->orderBy($column, $direction);

        return true;
    }
}
