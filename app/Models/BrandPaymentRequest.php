<?php

namespace App\Models;

use App\Enums\BrandPaymentRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BrandPaymentRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'brand_request_id',
        'tenant_id',
        'label',
        'amount',
        'currency',
        'status',
        'gateway_code',
        'transaction_id',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => BrandPaymentRequestStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function brandRequest(): BelongsTo
    {
        return $this->belongsTo(BrandRequest::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
