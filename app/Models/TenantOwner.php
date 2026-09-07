<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TenantOwner extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'tenant_id',
        'selected_tenant_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function selectedTenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'selected_tenant_id');
    }

    public function availableTenants(): \Illuminate\Database\Eloquent\Collection
    {
        return Tenant::query()->whereJsonContains('data->email', $this->email)->orderBy('created_at')->get();
    }
}
