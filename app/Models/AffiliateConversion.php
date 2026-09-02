<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateConversion extends Model
{
    protected $fillable = [
        'affiliate_id', 'affiliate_referral_id', 'tenant_id',
        'payment_log_id', 'package_id',
        'sale_amount', 'commission_amount',
        'commission_type', 'commission_value',
        'status', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'sale_amount'       => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'commission_value'  => 'decimal:2',
            'approved_at'       => 'datetime',
            'paid_at'           => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo   { return $this->belongsTo(Affiliate::class); }
    public function referral(): BelongsTo    { return $this->belongsTo(AffiliateReferral::class, 'affiliate_referral_id'); }
    public function paymentLog(): BelongsTo  { return $this->belongsTo(PaymentLog::class); }
    public function package(): BelongsTo     { return $this->belongsTo(Package::class); }
}
