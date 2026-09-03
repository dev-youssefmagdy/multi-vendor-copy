<?php

use App\Models\AdminRole;
use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        AdminRole::query()->get()->each(function (AdminRole $role): void {
            $permissions = AdminPermissions::normalize($role->permissions ?? [], $role->name);

            $role->forceFill([
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
            ])->save();
        });
    }

    public function down(): void
    {
        // Permission normalization is irreversible.
    }
};
