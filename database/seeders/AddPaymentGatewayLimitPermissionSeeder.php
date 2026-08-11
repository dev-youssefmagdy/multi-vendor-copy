<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use Illuminate\Database\Seeder;

class AddPaymentGatewayLimitPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perm = 'settings.payment-gateway-limits.manage';

        AdminRole::withoutEvents(function () use ($perm) {
            // Ensure Super Admin has the new permission
            $super = AdminRole::query()->where('name', 'Super Admin')->first();
            if ($super) {
                $perms = $super->permissions ?? [];
                if (!in_array($perm, $perms, true)) {
                    $perms[] = $perm;
                    $super->update(['permissions' => $perms, 'permissions_count' => count($perms)]);
                }
            }

            // Add new permission to roles that already have payment-gateways.manage
            AdminRole::query()->get()->each(function (AdminRole $role) use ($perm) {
                $perms = $role->permissions ?? [];
                if (!in_array($perm, $perms, true) && in_array('settings.payment-gateways.manage', $perms, true)) {
                    $perms[] = $perm;
                    $role->update(['permissions' => $perms, 'permissions_count' => count($perms)]);
                }
            });
        });
    }
}
