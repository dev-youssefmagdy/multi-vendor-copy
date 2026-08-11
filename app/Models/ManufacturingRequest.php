<?php

namespace App\Models;

use App\Enums\ManufacturingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ManufacturingRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_name',
        'description',
        'quantity',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ManufacturingRequestStatus::class,
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ManufacturingRequestMessage::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(ManufacturingPaymentRequest::class);
    }
}
