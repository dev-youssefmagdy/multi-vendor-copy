<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/** Dedup plan §5a.2: one per-tenant data step run (section, step, counts, status, error). */
class CatalogDedupRun extends Model
{
    use CentralConnection;

    public const RUNNING = 'running';
    public const DONE = 'done';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';

    protected $fillable = ['tenant_id', 'section', 'step', 'status', 'counts', 'error', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return [
            'counts' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
