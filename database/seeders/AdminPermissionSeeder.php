<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;

/**
 * Canonical admin permission seeder — idempotent, safe to run on any environment.
 *
 * Syncs the Super Admin role to exactly AdminPermissions::all() and strips
 * stale permissions (no longer in AdminPermissions::definitions()) from every role.
 */
class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = AdminPermissions::all();
        $definedSet = array_flip($allPermissions);

        AdminRole::withoutEvents(function () use ($allPermissions, $definedSet) {
            $superAdmin = AdminRole::query()->where('name', 'Super Admin')->first();

            if ($superAdmin) {
                $superAdmin->update([
                    'permissions' => $allPermissions,
                    'permissions_count' => count($allPermissions),
                ]);

                $this->command?->info('Super Admin synced: ' . count($allPermissions) . ' permissions.');
            } else {
                $this->command?->warn('Super Admin role not found — skipping.');
            }

            AdminRole::query()
                ->where('name', '!=', 'Super Admin')
                ->get()
                ->each(function (AdminRole $role) use ($definedSet) {
                    $current = $role->permissions ?? [];
                    $cleaned = array_values(array_filter(
                        $current,
                        fn (string $p) => isset($definedSet[$p])
                    ));

                    $removed = count($current) - count($cleaned);

                    if ($removed > 0) {
                        $role->update([
                            'permissions' => $cleaned,
                            'permissions_count' => count($cleaned),
                        ]);

                        $this->command?->line("  Role \"{$role->name}\": removed {$removed} stale permission(s).");
                    }
                });
        });

        $this->command?->info('AdminPermissionSeeder complete.');
    }
}
