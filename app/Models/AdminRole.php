<?php

namespace App\Models;

use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'permissions',
        'permissions_count',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'permissions_count' => 'integer',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }

    public function normalizedPermissions(): array
    {
        return AdminPermissions::normalize($this->permissions ?? [], $this->name);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->normalizedPermissions(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
