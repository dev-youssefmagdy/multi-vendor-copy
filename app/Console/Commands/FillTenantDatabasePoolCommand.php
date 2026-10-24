<?php

namespace App\Console\Commands;

use App\Services\Tenant\TenantDatabasePool;
use Illuminate\Console\Command;

class FillTenantDatabasePoolCommand extends Command
{
    protected $signature = 'tenants:pool-fill {--size= : Ready databases to keep (default: tenancy.database_pool_size)}';

    protected $description = 'Pre-create migrated tenant databases so new tenants are provisioned without running DDL';

    public function handle(TenantDatabasePool $pool): int
    {
        $size = $this->option('size') !== null ? (int) $this->option('size') : $pool->targetSize();

        if ($size <= 0) {
            $this->components->warn('Tenant database pool is disabled (TENANT_DB_POOL_SIZE=0).');

            return self::SUCCESS;
        }

        $created = $pool->fill($size, fn (int $done, int $total) => $this->components->info("Created {$done}/{$total}"));

        $this->components->info("Pool ready: {$pool->available()} database(s)" . ($created ? " ({$created} created)" : ''));

        return self::SUCCESS;
    }
}
