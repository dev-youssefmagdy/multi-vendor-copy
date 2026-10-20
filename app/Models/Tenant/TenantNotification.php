<?php

namespace App\Models\Tenant;

use App\Models\Concerns\FormatsNotificationFeed;
use App\Support\NotificationLinks;
use Illuminate\Database\Eloquent\Model;

class TenantNotification extends Model
{
    use FormatsNotificationFeed;

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

    public function targetUrl(): ?string
    {
        return NotificationLinks::tenant((string) $this->type, $this->data);
    }
}
