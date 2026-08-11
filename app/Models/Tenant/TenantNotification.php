<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantNotification extends Model
{
    protected $table = 'tenant_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
        ];
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
