<?php

namespace App\Models\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\PaymentGateway;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Payment methods where the customer pays the courier in cash on delivery. Such an order is
     * never flagged `paid`, but once it is Delivered / Completed the cash counts as collected
     * (see isPaymentCollected()).
     */
    public const CASH_ON_DELIVERY_METHODS = ['cod', 'cash_on_delivery', 'cash'];

    protected $fillable = [
        'uuid',
        'order_group_uuid',
        'customer_id',
        'shipping_address',
        'payment_method',
        'status',
        'paid',
        'payment_details',
        'discount_id',
        'discount_percentage',
        'tax_percentage',
        'shipping_zone_rate_id',
        'shipping_charge',
        'coupon_discount',
        'coupon_source',
        'platform_profit',
        'vendor_profit',
        'collected_by',
        'payment_gateway_id',
        'vendor_gateway_id',
        'vendor_gateway_fee',
        'vendor_settled_at',
        'vendor_settlement_ref',
        'tracking_number',
        'payout_released',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_note',
        'cancelled_by_type',
        'cancelled_by_id',
        'stock_deducted_at',
        'stock_restored_at',
        'refunded_amount',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'status' => OrderStatus::class,
            'paid' => 'boolean',
            'payout_released' => 'boolean',
            'payment_details' => 'array',
            'discount_percentage' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'platform_profit' => 'decimal:2',
            'vendor_profit' => 'decimal:2',
            'vendor_gateway_fee' => 'decimal:2',
            'vendor_settled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancellation_reason' => CancellationReason::class,
            'cancelled_by_type' => CancellationActor::class,
            'cancelled_by_id' => 'integer',
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
            'refunded_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'discount_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    /**
     * Refunds live in the central DB (keyed by tenant + order uuid), so this is a query,
     * not an Eloquent relation. Defaults to the current tenant.
     */
    public function refundsQuery(?string $tenantId = null): Builder
    {
        return Refund::query()->forOrder($tenantId ?? (string) tenant('id'), (string) $this->uuid);
    }

    // ─── State helpers ──────────────────────────────────────────────────────

    /** Cancelled by anyone, or rejected by the vendor. */
    public function isCancelled(): bool
    {
        return $this->status instanceof OrderStatus && $this->status->isCancelled();
    }

    public function isRefunded(): bool
    {
        return $this->status === OrderStatus::Refunded;
    }

    /** Paid / to be paid in cash to the courier. */
    public function isCashOnDelivery(): bool
    {
        return in_array(strtolower((string) $this->payment_method), self::CASH_ON_DELIVERY_METHODS, true);
    }

    /**
     * The customer's money is in the store's hands: the order is `paid`, or it is a cash-on-delivery
     * order that reached the customer (Delivered / Completed), so the courier collected the cash.
     * Used for refund eligibility only — it does NOT change `paid` or payout / ledger figures.
     * Refunds of collected-but-unpaid (COD) orders are always settled manually.
     */
    public function isPaymentCollected(): bool
    {
        if ($this->paid) {
            return true;
        }

        return $this->isCashOnDelivery()
            && $this->status instanceof OrderStatus
            && $this->status->isDelivered();
    }

    public function stockWasDeducted(): bool
    {
        return $this->stock_deducted_at !== null;
    }

    /** Stock was taken for this order and has not been given back yet. */
    public function needsStockRestore(): bool
    {
        return $this->stock_deducted_at !== null && $this->stock_restored_at === null;
    }

    /**
     * Customer-facing payment state, derived from paid + refunded_amount:
     * fully refunded → Refunded, partly refunded → PartiallyRefunded, otherwise Paid / Unpaid.
     */
    public function paymentState(): OrderPaymentStatus
    {
        $refunded = round((float) ($this->refunded_amount ?? 0), 2);

        if ($refunded > 0) {
            return ($refunded >= $this->grand_total || $this->status === OrderStatus::Refunded)
                ? OrderPaymentStatus::Refunded
                : OrderPaymentStatus::PartiallyRefunded;
        }

        return $this->paid ? OrderPaymentStatus::Paid : OrderPaymentStatus::Unpaid;
    }

    /**
     * Amount that can still be refunded based on COMPLETED refunds only
     * (grand_total − refunded_amount, never negative; 0 when no payment was collected —
     * see isPaymentCollected()). Pending/processing refunds are reserved separately by the
     * refund service.
     */
    public function remainingRefundable(): float
    {
        if (! $this->isPaymentCollected()) {
            return 0.0;
        }

        return max(0.0, round($this->grand_total - (float) ($this->refunded_amount ?? 0), 2));
    }

    // ─── Calculated Getters (docs/ORDER_FORMULAS.md) ────────────────────────

    /** Σ price × qty of all lines (before fixed shipping / discounts). */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum('sub_total');
    }

    /** Σ line fixed shipping ("fixed cost price"). */
    public function getItemsFixedShippingAttribute(): float
    {
        return (float) $this->items->sum('fixed_shipping');
    }

    /** Σ line flash-sale discounts. */
    public function getItemsDiscountAttribute(): float
    {
        return (float) $this->items->sum('discount');
    }

    /** Σ line taxes (= order tax). */
    public function getItemsTaxAttribute(): float
    {
        return (float) $this->items->sum('tax');
    }

    /** Σ line shipping fees. */
    public function getItemsShippingAttribute(): float
    {
        return (float) $this->items->sum('shipping_fee');
    }

    /** items_grand_total = Σ item grand_total. */
    public function getItemsGrandTotalAttribute(): float
    {
        return round((float) $this->items->sum('grand_total'), 2);
    }

    /** Coupon discount applied on the order. */
    public function getDiscountAmountAttribute(): float
    {
        return round((float) ($this->coupon_discount ?? 0), 2);
    }

    public function getTaxAmountAttribute(): float
    {
        return round($this->items_tax, 2);
    }

    /** Prefer the order-level shipping charge and fall back to summed item shipping. */
    public function getResolvedShippingChargeAttribute(): float
    {
        $shippingCharge = (float) ($this->shipping_charge ?? 0);

        if ($shippingCharge > 0) {
            return $shippingCharge;
        }

        return round($this->items_shipping, 2);
    }

    /** order_grand_total = items_grand_total + shipping - coupon discount. */
    public function getGrandTotalAttribute(): float
    {
        return round($this->items_grand_total + $this->resolved_shipping_charge - $this->discount_amount, 2);
    }

    /** True when the customer's money went to the vendor (own gateway / COD). */
    public function getCollectedByVendorAttribute(): bool
    {
        return $this->collected_by === 'vendor';
    }

    /** Σ vendor -> platform settlements recorded for this order. */
    public function getVendorSettlementsPaidAttribute(): float
    {
        return $this->transactionsTotal(OrderTransaction::TYPE_VENDOR_SETTLEMENT);
    }

    /** Σ platform -> vendor payouts recorded for this order. */
    public function getPlatformPayoutsPaidAttribute(): float
    {
        return $this->transactionsTotal(OrderTransaction::TYPE_PLATFORM_PAYOUT);
    }

    private function transactionsTotal(string $type): float
    {
        if (! $this->relationLoaded('transactions') && ! $this->exists) {
            return 0.0;
        }

        $rows = $this->relationLoaded('transactions') ? $this->transactions : $this->transactions()->get();

        return round((float) $rows->where('type', $type)->sum('amount'), 2);
    }

    /** Platform profit + the gateway fee the vendor pays when settling. */
    public function getVendorTotalWithFeeAttribute(): float
    {
        return round((float) ($this->platform_profit ?? 0) + (float) ($this->vendor_gateway_fee ?? 0), 2);
    }

    /** Whether the vendor has settled payment for this order's platform share. */
    public function getVendorSettledAttribute(): bool
    {
        return $this->vendor_settled_at !== null;
    }
}
