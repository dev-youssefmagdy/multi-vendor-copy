<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use Illuminate\Database\Seeder;

class AddReturnPolicyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perm = 'settings.general.manage';

        AdminRole::withoutEvents(function () use ($perm) {
            // Ensure Super Admin has the permission
            $super = AdminRole::query()->where('name', 'Super Admin')->first();
            if ($super) {
                $perms = $super->permissions ?? [];
                if (!in_array($perm, $perms, true)) {
                    $perms[] = $perm;
                    $super->update(['permissions' => $perms, 'permissions_count' => count($perms)]);
                }
            }
        });
    }
}
