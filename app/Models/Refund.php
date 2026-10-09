<?php

namespace App\Models;

use App\Enums\CancellationActor;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A refund of (part of) a tenant order. Stored centrally — like return requests — so the
 * platform admin can see every refund across tenants. Keyed by tenant_id + order_number
 * (the tenant order uuid).
 *
 * @property string $reference
 * @property RefundStatus $status
 * @property RefundSource $source
 * @property RefundMethod $refund_method
 */
class Refund extends Model
{
    use CentralConnection;

    protected $fillable = [
        'reference',
        'tenant_id',
        'order_number',
        'return_request_id',
        'source',
        'reason',
        'currency',
        'items_amount',
        'shipping_amount',
        'return_fee',
        'amount',
        'payment_method',
        'gateway',
        'original_transaction_id',
        'refund_method',
        'gateway_refund_id',
        'status',
        'failure_reason',
        'requested_by_type',
        'requested_by_id',
        'approved_by_type',
        'approved_by_id',
        'approved_by_name',
        'requested_at',
        'approved_at',
        'processed_at',
        'notes',
        'meta',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'USD',
        'shipping_amount' => 0,
        'return_fee' => 0,
    ];

    protected function casts(): array
    {
        return [
            'source' => RefundSource::class,
            'refund_method' => RefundMethod::class,
            'status' => RefundStatus::class,
            'requested_by_type' => CancellationActor::class,
            'approved_by_type' => CancellationActor::class,
            'requested_by_id' => 'integer',
            'approved_by_id' => 'integer',
            'items_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'return_fee' => 'decimal:2',
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $refund): void {
            $refund->reference ??= static::generateReference();
            $refund->requested_at ??= now();
        });
    }

    /** RF-{YYYYMMDD}-{RANDOM6}, unique. */
    public static function generateReference(): string
    {
        do {
            $reference = 'RF-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeForOrder(Builder $query, string $tenantId, string $orderNumber): Builder
    {
        return $query->where('tenant_id', $tenantId)->where('order_number', $orderNumber);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /** Refunds whose amount counts against the order's refundable total (pending/processing/completed). */
    public function scopeReserving(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (RefundStatus $s) => $s->value, RefundStatus::reservingStatuses()));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === RefundStatus::Completed;
    }
}
