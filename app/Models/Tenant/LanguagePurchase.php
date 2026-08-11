<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanguagePurchase extends Model
{
    protected $table = 'language_purchases';

    protected $fillable = [
        'central_language_id',
        'language_code',
        'language_name',
        'amount',
        'gateway_code',
        'transaction_uuid',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
