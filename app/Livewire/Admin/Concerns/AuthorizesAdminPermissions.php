<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\AdminUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait AuthorizesAdminPermissions
{
    protected function adminUser(): ?AdminUser
    {
        $user = auth('admin')->user();

        return $user instanceof AdminUser ? $user : null;
    }

    protected function hasPermission(string $permission): bool
    {
        return $this->adminUser()?->hasPermission($permission) ?? false;
    }

    protected function hasAnyPermission(array $permissions): bool
    {
        return $this->adminUser()?->hasAnyPermission($permissions) ?? false;
    }

    protected function authorizePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            throw new AccessDeniedHttpException('You do not have permission to perform that action.');
        }
    }

    protected function authorizeAnyPermission(array $permissions): void
    {
        if (!$this->hasAnyPermission($permissions)) {
            throw new AccessDeniedHttpException('You do not have permission to perform that action.');
        }
    }
}
