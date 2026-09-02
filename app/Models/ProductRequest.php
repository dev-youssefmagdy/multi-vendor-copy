<?php

namespace App\Models;

use App\Enums\ProductRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'attachments',
        'status',
        'tenant_has_unread',
        'admin_has_unread',
        'last_reply_at',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'attachments'       => 'array',
            'status'            => ProductRequestStatus::class,
            'tenant_has_unread' => 'boolean',
            'admin_has_unread'  => 'boolean',
            'last_reply_at'     => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function messages(): HasMany
    {
        return $this->hasMany(ProductRequestMessage::class)->orderBy('created_at');
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ProductRequestStatus::Completed->value,
            ProductRequestStatus::Rejected->value,
        ]);
    }
}
