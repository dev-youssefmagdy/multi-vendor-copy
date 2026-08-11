<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'central_currency_id',
        'code',
        'name',
        'symbol',
        'conversion_rate',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'conversion_rate' => 'decimal:6',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
