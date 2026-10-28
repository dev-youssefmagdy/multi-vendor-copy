<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileAccessToken extends Model
{
    protected $fillable = [
        'tenant_id', 'customer_id', 'device_name', 'token_hash', 'refresh_hash',
        'last_used_at', 'expires_at', 'revoked_at',
    ];

    protected $hidden = ['token_hash', 'refresh_hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
