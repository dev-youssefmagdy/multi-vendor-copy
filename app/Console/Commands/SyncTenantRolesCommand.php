<?php

namespace App\Console\Commands;

use App\Models\Tenant\AdminRole;
use Illuminate\Console\Command;

class SyncTenantRolesCommand extends Command
{
    protected $signature = 'tenants:sync-roles';
    protected $description = 'Sync default role permissions to all tenant databases';

    public function handle(): int
    {
        $tenants = \App\Models\Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            foreach (AdminRole::defaultRoleDefinitions() as $roleName => $permissions) {
                AdminRole::query()->updateOrCreate(
                    ['name' => $roleName],
                    [
                        'permissions' => $permissions,
                        'permissions_count' => count($permissions),
                    ]
                );
            }

            tenancy()->end();

            $this->line("✓ {$tenant->id}");
        }

        $this->info('All tenant roles synced.');

        return self::SUCCESS;
    }
}
