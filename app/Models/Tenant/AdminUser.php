<?php

namespace App\Models\Tenant;

use App\Enums\ActivationStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'admins';

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'status',
        'last_login_at',
        'tour_seen_at',
        'setup_dismissed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
            'last_login_at' => 'datetime',
            'tour_seen_at' => 'datetime',
            'setup_dismissed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
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
