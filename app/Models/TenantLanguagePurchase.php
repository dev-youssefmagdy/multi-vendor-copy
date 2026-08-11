<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantLanguagePurchase extends Model
{
    use CentralConnection;

    protected $table = 'tenant_language_purchases';

    protected $fillable = [
        'tenant_id',
        'central_language_id',
        'transaction_uuid',
        'amount',
        'gateway_code',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'central_language_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
