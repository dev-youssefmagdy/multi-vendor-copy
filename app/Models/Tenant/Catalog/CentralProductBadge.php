<?php

namespace App\Models\Tenant\Catalog;

/**
 * Read-only central `product_badges` row on the tenant connection. Example for phase 2 (badges); not wired yet.
 *
 * @property int $id
 * @property string $text
 * @property bool $active
 */
class CentralProductBadge extends CentralReadModel
{
    protected string $centralTable = 'product_badges';

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
