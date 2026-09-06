<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TenantPermissionSeeder;

class TenantPermissionSeed extends Command
{
    protected $signature = 'tenant:permission-seed';

    protected $description = 'Run TenantPermissionSeeder';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => TenantPermissionSeeder::class,
        ]);

        return self::SUCCESS;
    }
}
