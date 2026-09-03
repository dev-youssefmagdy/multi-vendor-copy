<?php

namespace App\Models\Tenant;

use App\Enums\WalletTransactionDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'amount',
        'type',
        'model_type',
        'model_id',
        'admin_profit',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => WalletTransactionDirection::class,
            'admin_profit' => 'decimal:2',
        ];
    }

    public function model()
    {
        return $this->morphTo();
    }
}
