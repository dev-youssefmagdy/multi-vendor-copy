<?php

namespace Database\Seeders;

use App\Models\Tenant\AdminRole;
use Illuminate\Database\Seeder;

/**
 * Syncs tenant admin role permissions to the current AdminRole::availablePermissions() definition.
 *
 * Idempotent — safe to run multiple times.
 *
 * What it does:
 *   1. Store Owner: set to exactly all defined permissions.
 *   2. Manager / Support: apply the canonical defaultRoleDefinitions() if the role exists.
 *   3. All roles: strip any stale permissions no longer in availablePermissions().
 *
 * Run per-tenant:
 *   php artisan tenants:run db:seed --class=TenantPermissionSeeder
 * Or on a specific tenant:
 *   php artisan tenants:run db:seed --class=TenantPermissionSeeder --tenants=TENANT_ID
 */
class TenantPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allKeys = array_keys(AdminRole::availablePermissions());
        $definedSet = array_flip($allKeys);
        $defaults = AdminRole::defaultRoleDefinitions();

        AdminRole::withoutEvents(function () use ($allKeys, $definedSet, $defaults) {
            AdminRole::query()->get()->each(function (AdminRole $role) use ($allKeys, $definedSet, $defaults) {
                $current = $role->permissions ?? [];

                if ($role->name === AdminRole::STORE_OWNER) {
                    $newPerms = $allKeys;
                } elseif (isset($defaults[$role->name])) {
                    $merged = array_unique(array_merge($current, $defaults[$role->name]));
                    $newPerms = array_values(array_filter(
                        $merged,
                        fn (string $p) => isset($definedSet[$p])
                    ));
                } else {
                    $newPerms = array_values(array_filter(
                        $current,
                        fn (string $p) => isset($definedSet[$p])
                    ));
                }

                $added = count(array_diff($newPerms, $current));
                $removed = count(array_diff($current, $newPerms));

                $role->update([
                    'permissions' => $newPerms,
                    'permissions_count' => count($newPerms),
                ]);

                if ($added > 0 || $removed > 0) {
                    $this->command?->line(
                        sprintf('  [%s] "%s": +%d added, -%d removed → %d total',
                            tenant()?->getTenantKey() ?? 'central',
                            $role->name,
                            $added,
                            $removed,
                            count($newPerms)
                        )
                    );
                }
            });
        });
    }
}
