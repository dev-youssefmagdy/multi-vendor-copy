<?php

namespace App\Models;

use App\Enums\ActivationStatus;
use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PaymentGateway extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'name',
        'code',
        'type',
        'status',
        'mode',
        'credentials',
        'logo_file_id',
        'transaction_fee_percentage',
        'transaction_fee_fixed',
    ];

    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
            'mode' => PaymentGatewayMode::class,
            'type' => PaymentGatewayType::class,
            'credentials' => 'encrypted:array',
            'transaction_fee_percentage' => 'decimal:4',
            'transaction_fee_fixed' => 'decimal:2',
        ];
    }

    /**
     * Calculate the gateway fee for a given amount.
     * fee = fixed + (percentage / 100) * amount
     */
    public function calculateFee(float $amount): float
    {
        $pct = (float) ($this->transaction_fee_percentage ?? 0);
        $fixed = (float) ($this->transaction_fee_fixed ?? 0);

        return round($fixed + ($pct / 100) * $amount, 2);
    }
}
