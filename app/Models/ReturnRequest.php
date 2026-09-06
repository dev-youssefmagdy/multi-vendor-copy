<?php

namespace App\Models;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AdminUser;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ReturnRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'product_id',
        'product_variant_id',
        'reason',
        'description',
        'status',
        'refund_amount',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'reason' => ReturnReason::class,
        'reviewed_at' => 'datetime',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(ReturnRequestMedia::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ReturnRequestNote::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }
}
