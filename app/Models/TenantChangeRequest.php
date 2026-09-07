<?php

namespace App\Models;

use App\Enums\TenantChangeRequestStatus;
use App\Enums\TenantChangeRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantChangeRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'type',
        'requested_data',
        'current_data',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TenantChangeRequestType::class,
            'requested_data' => 'array',
            'current_data' => 'array',
            'status' => TenantChangeRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function scopePending($query)
    {
        return $query->where('status', TenantChangeRequestStatus::Pending);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, TenantChangeRequestType $type)
    {
        return $query->where('type', $type);
    }
}
