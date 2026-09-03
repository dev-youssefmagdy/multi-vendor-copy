<?php

namespace App\Models;

use App\Enums\ProductEditRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProductEditRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_slug',
        'requested_translations',
        'current_translations',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_translations' => 'array',
            'current_translations' => 'array',
            'status' => ProductEditRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', ProductEditRequestStatus::Pending);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
