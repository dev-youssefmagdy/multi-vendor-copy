<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'email',
        'phone',
        'locale',
        'package_id',
        'category_ids',
        'country_ids',
        'payment_data',
        'expires_at',
        'completed_at',
        'affiliate_referral_id',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'country_ids' => 'array',
        'payment_data' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function affiliateReferral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isCompleted();
    }

    public function markCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }
}
