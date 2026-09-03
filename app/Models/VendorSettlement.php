<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class VendorSettlement extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'order_uuid',
        'invoice_number',
        'tenant_name',
        'tenant_email',
        'product_cost',
        'shipping_cost',
        'gateway_fee',
        'total',
        'gateway_code',
        'transaction_id',
        'status',
        'meta',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'product_cost' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
            'settled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /** Generate next invoice number for this tenant. */
    public static function nextInvoiceNumber(string $tenantId): string
    {
        $count = static::query()
            ->where('tenant_id', $tenantId)
            ->count();

        return sprintf('VS-%s-%04d', strtoupper(substr($tenantId, 0, 8)), $count + 1);
    }
}
