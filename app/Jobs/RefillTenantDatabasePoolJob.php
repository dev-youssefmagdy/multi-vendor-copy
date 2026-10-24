<?php

namespace App\Jobs;

use App\Services\Tenant\TenantDatabasePool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Tops the pool back up after a tenant claimed a database — on `default`, never on the provisioning queue. */
class RefillTenantDatabasePoolJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 600;
    public int $uniqueFor = 600;

    public function handle(TenantDatabasePool $pool): void
    {
        $pool->fill();
    }
}
