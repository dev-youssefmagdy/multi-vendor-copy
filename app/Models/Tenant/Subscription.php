<?php

namespace App\Models\Tenant;

use App\Enums\PackageTerm;
use App\Enums\Tenant\SubscriptionGatewayType;
use App\Enums\Tenant\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'price',
        'status',
        'term',
        'payment_gateway_type',
        'payment_gateway_id',
        'transaction_id',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => SubscriptionStatus::class,
            'term' => PackageTerm::class,
            'payment_gateway_type' => SubscriptionGatewayType::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
