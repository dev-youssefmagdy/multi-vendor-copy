<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subject',
        'status',
        'priority',
        'category',
        'tenant_has_unread',
        'admin_has_unread',
        'last_reply_at',
    ];

    protected $casts = [
        'tenant_has_unread' => 'boolean',
        'admin_has_unread' => 'boolean',
        'last_reply_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public static function statusOptions(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'general' => 'General',
            'billing' => 'Billing',
            'technical' => 'Technical',
            'order' => 'Order',
            'return' => 'Return',
        ];
    }
}
