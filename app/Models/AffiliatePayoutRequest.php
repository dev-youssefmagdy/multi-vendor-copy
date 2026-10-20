<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayoutRequest extends Model
{
    protected $fillable = [
        'affiliate_id', 'amount', 'status', 'note', 'admin_note',
        'affiliate_payout_id', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'affiliate_payout_id');
    }
}
