<?php

use App\Models\Tenant\AdminRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private array $permissions = ['store.custom-template.manage'];

    public function up(): void
    {
        AdminRole::query()->get()->each(function (AdminRole $role): void {
            $current = $role->permissions ?? [];
            $updated = array_values(array_filter($current, fn(string $p) => !in_array($p, $this->permissions, true)));

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
        // Permission was legacy; not restored on rollback.
    }
};
