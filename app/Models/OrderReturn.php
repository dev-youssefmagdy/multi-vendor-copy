<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OrderReturn extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'status',
        'reason',
        'customer_notes',
        'admin_notes',
        'refund_amount',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected $casts = [
        'status'      => ReturnStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }
}
