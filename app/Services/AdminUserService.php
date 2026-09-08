<?php

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function save(array $attributes, ?AdminUser $admin = null): AdminUser
    {
        return DB::transaction(function () use ($attributes, $admin) {
            $admin ??= new AdminUser();
            $admin->fill([
                'role_id' => $attributes['role_id'] ?: null,
                'name' => $attributes['name'],
                'email' => strtolower(trim((string) $attributes['email'])),
                'status' => $attributes['status'],
                'last_login_at' => $attributes['last_login_at'] ?? $admin->last_login_at,
            ]);

            if (filled($attributes['password'] ?? null)) {
                $admin->password = Hash::make((string) $attributes['password']);
            }

            $admin->save();

            return $admin->fresh('role');
        });
    }

    public function delete(AdminUser $admin): void
    {
        $admin->delete();
    }
}
