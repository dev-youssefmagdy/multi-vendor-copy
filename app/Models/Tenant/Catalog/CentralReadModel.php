<?php

namespace App\Models\Tenant\Catalog;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for read-only central models used from tenant code (dedup plan §3.2). Subclasses only set
 * `protected string $centralTable`. Nothing is wired to these yet (phase 0 = no behaviour change).
 */
abstract class CentralReadModel extends Model
{
    use ReadOnlyCentralModel;

    protected string $centralTable;

    protected $guarded = [];
}
