<?php

namespace App\Models\Tenant;

use App\Enums\OrderStatus;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
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
        'owner_profit',
        'payment_gateway_id',
        'vendor_cost',
        'vendor_gateway_id',
        'vendor_gateway_fee',
        'vendor_settled_at',
        'vendor_settlement_ref',
        'tracking_number',
        'payout_released',
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
            'owner_profit' => 'decimal:2',
            'vendor_cost' => 'decimal:2',
            'vendor_gateway_fee' => 'decimal:2',
            'vendor_settled_at' => 'datetime',
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

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    // ─── Calculated Getters ─────────────────────────────────────────────────

    /**
     * Sum of all order items' sub_total (qty × price before item-level discount).
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum('sub_total');
    }

    /**
     * Sum of all item-level discounts already applied per line.
     */
    public function getItemsDiscountAttribute(): float
    {
        return (float) $this->items->sum('discount');
    }

    /**
     * Sum of all item-level taxes already applied per line.
     */
    public function getItemsTaxAttribute(): float
    {
        return (float) $this->items->sum('tax');
    }

    /**
     * Sum of all item-level shipping charges already stored per line.
     */
    public function getItemsShippingAttribute(): float
    {
        return (float) $this->items->sum('shipping_fee');
    }

    /**
     * Order-level discount derived from discount_percentage applied on subtotal.
     */
    public function getDiscountAmountAttribute(): float
    {
        return round($this->subtotal * ((float) $this->discount_percentage / 100), 2);
    }

    /**
     * Order-level tax derived from tax_percentage applied on subtotal.
     */
    public function getTaxAmountAttribute(): float
    {
        return round($this->subtotal * ((float) $this->tax_percentage / 100), 2);
    }

    /**
     * Prefer the order-level shipping charge and fall back to summed item shipping.
     */
    public function getResolvedShippingChargeAttribute(): float
    {
        $shippingCharge = (float) ($this->shipping_charge ?? 0);

        if ($shippingCharge > 0) {
            return $shippingCharge;
        }

        return round($this->items_shipping, 2);
    }

    /**
     * Grand total: subtotal minus order discount, plus order tax, plus shipping charge.
     * Item-level discounts / taxes are already reflected in sub_total values.
     */
    public function getGrandTotalAttribute(): float
    {
        return round(
            $this->subtotal
            - $this->discount_amount
            + $this->tax_amount
            + $this->resolved_shipping_charge,
            2
        );
    }

    /**
     * Total the vendor owes central: product cost + shipping charge.
     */
    public function getVendorTotalDueAttribute(): float
    {
        return round((float) ($this->vendor_cost ?? 0) + (float) ($this->shipping_charge ?? 0), 2);
    }

    /**
     * Total the vendor owes central including gateway fee.
     */
    public function getVendorTotalWithFeeAttribute(): float
    {
        return round($this->vendor_total_due + (float) ($this->vendor_gateway_fee ?? 0), 2);
    }

    /**
     * Whether the vendor has settled payment for this order's product cost.
     */
    public function getVendorSettledAttribute(): bool
    {
        return $this->vendor_settled_at !== null;
    }
}
