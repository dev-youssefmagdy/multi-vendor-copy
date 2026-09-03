<?php

namespace App\Models;

use App\Enums\ManufacturingPaymentRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ManufacturingPaymentRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'manufacturing_request_id',
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
            'status' => ManufacturingPaymentRequestStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function manufacturingRequest(): BelongsTo
    {
        return $this->belongsTo(ManufacturingRequest::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
