<?php

use App\Models\Tenant\AdminRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private array $permissions = [
        'store.custom-template.manage',
        'settings.tracking.manage',
    ];

    public function up(): void
    {
        AdminRole::query()->get()->each(function (AdminRole $role): void {
            $definitions = AdminRole::defaultRoleDefinitions();

            if (!isset($definitions[$role->name])) {
                return;
            }

            $current = $role->permissions ?? [];
            $updated = $current;

            foreach ($this->permissions as $permission) {
                if (in_array($permission, $updated, true)) {
                    continue;
                }
                if (!in_array($permission, $definitions[$role->name], true)) {
                    continue;
                }
                $updated[] = $permission;
            }

            $updated = array_values(array_unique($updated));

            if ($updated === $current) {
                return;
            }

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
            $updated = array_values(array_filter($current, fn(string $p) => !in_array($p, $this->permissions, true)));

            $role->forceFill([
                'permissions' => $updated,
                'permissions_count' => count($updated),
            ])->save();
        });
    }
};
