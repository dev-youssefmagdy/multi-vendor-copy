<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One money movement between vendor and platform for an order (see docs/ORDER_FORMULAS.md). */
class OrderTransaction extends Model
{
    public const TYPE_VENDOR_SETTLEMENT = 'vendor_settlement';
    public const TYPE_PLATFORM_PAYOUT = 'platform_payout';

    protected $fillable = ['order_id', 'type', 'amount', 'gateway_fee', 'reference', 'gateway_id', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
