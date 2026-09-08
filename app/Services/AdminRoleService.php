<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\DB;

class AdminRoleService
{
    public function save(array $attributes, ?AdminRole $role = null): AdminRole
    {
        return DB::transaction(function () use ($attributes, $role) {
            $role ??= new AdminRole();
            $permissions = AdminPermissions::normalize($attributes['permissions'] ?? [], $attributes['name'] ?? $role->name);

            $role->fill([
                'name' => $attributes['name'],
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
            ]);
            $role->save();

            return $role->fresh('admins');
        });
    }

    public function delete(AdminRole $role): void
    {
        DB::transaction(function () use ($role) {
            $role->admins()->update(['role_id' => null]);
            $role->delete();
        });
    }
}
