<?php

use App\Models\Tenant\AdminRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $permission = 'settings.domains.manage';

        AdminRole::query()->get()->each(function (AdminRole $role) use ($permission): void {
            $definitions = AdminRole::defaultRoleDefinitions();

            if (!isset($definitions[$role->name])) {
                return;
            }

            $current = $role->permissions ?? [];

            if (in_array($permission, $current, true)) {
                return;
            }

            if (!in_array($permission, $definitions[$role->name], true)) {
                return;
            }

            $updated = array_values(array_unique([...$current, $permission]));

            $role->forceFill([
                'permissions' => $updated,
                'permissions_count' => count($updated),
            ])->save();
        });
    }

    public function down(): void
    {
        $permission = 'settings.domains.manage';

        AdminRole::query()->get()->each(function (AdminRole $role) use ($permission): void {
            $current = $role->permissions ?? [];
            $updated = array_values(array_filter($current, fn(string $p) => $p !== $permission));

            $role->forceFill([
                'permissions' => $updated,
                'permissions_count' => count($updated),
            ])->save();
        });
    }
};
