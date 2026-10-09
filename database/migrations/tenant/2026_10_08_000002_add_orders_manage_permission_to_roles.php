<?php

use App\Models\Tenant\AdminRole;
use Illuminate\Database\Migrations\Migration;

/**
 * Grants the new `sales.orders.manage` permission (cancel orders from the panel) to the default
 * roles whose canonical definition includes it (Store Owner, Manager). Custom roles are left
 * alone — the store owner grants it from Settings → Roles & Permissions.
 */
return new class extends Migration
{
    private const PERMISSION = 'sales.orders.manage';

    public function up(): void
    {
        $definitions = AdminRole::defaultRoleDefinitions();

        AdminRole::query()->get()->each(function (AdminRole $role) use ($definitions): void {
            if (! isset($definitions[$role->name]) || ! in_array(self::PERMISSION, $definitions[$role->name], true)) {
                return;
            }

            $current = $role->permissions ?? [];

            if (in_array(self::PERMISSION, $current, true)) {
                return;
            }

            $updated = array_values(array_unique([...$current, self::PERMISSION]));

            $role->forceFill([
                'permissions' => $updated,
                'permissions_count' => count($updated),
            ])->save();
        });
    }

    public function down(): void
    {
        AdminRole::query()->get()->each(function (AdminRole $role): void {
            $current = $role->permissions ?? [];
            $updated = array_values(array_filter($current, fn (string $p) => $p !== self::PERMISSION));

            $role->forceFill([
                'permissions' => $updated,
                'permissions_count' => count($updated),
            ])->save();
        });
    }
};
