<?php

namespace App\Models;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Enums\ReturnMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ReturnRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'type',
        'customer_id',
        'order_item_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'return_method',
        'reason',
        'description',
        'customer_note',
        'status',
        'refund_amount',
        'inspection_result',
        'inspection_notes',
        'received_at',
        'inspected_at',
        'restocked_at',
        'replacement_product_variant_id',
        'replacement_quantity',
        'replacement_reserved_at',
        'exchange_tracking_number',
        'exchange_shipped_at',
        'exchange_completed_at',
        'cancelled_at',
        'forwarded_at',
        'reviewed_by_admin_id',
        'reviewed_by_type',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $attributes = [
        'type' => 'return',
        'quantity' => 1,
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'reason' => ReturnReason::class,
        'type' => ReturnType::class,
        'return_method' => ReturnMethod::class,
        'inspection_result' => InspectionResult::class,
        'reviewed_by_type' => CancellationActor::class,
        'quantity' => 'integer',
        'replacement_quantity' => 'integer',
        'refund_amount' => 'decimal:2',
        'received_at' => 'datetime',
        'inspected_at' => 'datetime',
        'restocked_at' => 'datetime',
        'replacement_reserved_at' => 'datetime',
        'exchange_shipped_at' => 'datetime',
        'exchange_completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'forwarded_at' => 'datetime',
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

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isExchange(): bool
    {
        return $this->type === ReturnType::Exchange;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }
}
