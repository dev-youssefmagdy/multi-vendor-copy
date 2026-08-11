<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Central record of payouts FROM the platform owner TO a tenant for the
 * tenant's net order share that was collected through central-owned gateways.
 */
class TenantPayout extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'tenant_name',
        'tenant_email',
        'amount',
        'status',
        'currency',
        'method',
        'bank_name',
        'bank_account',
        'gateway_name',
        'transaction_reference',
        'note',
        'orders_snapshot',
        'admin_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'orders_snapshot' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /** Generate next sequential invoice number for a tenant. */
    public static function nextInvoiceNumber(string $tenantId): string
    {
        $count = static::query()->where('tenant_id', $tenantId)->count();

        return sprintf('TP-%s-%04d', strtoupper(substr($tenantId, 0, 8)), $count + 1);
    }
}
