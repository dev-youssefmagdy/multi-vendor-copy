<?php

namespace App\Models;

use App\Enums\PaymentLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PaymentLog extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'tenant_id',
        'package_id',
        'gateway',
        'amount',
        'status',
        'reference',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentLogStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
