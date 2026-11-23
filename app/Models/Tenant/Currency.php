<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant\Concerns\OverlaysCentralConfig;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use OverlaysCentralConfig;
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

    protected static function configCatalogSection(): string
    {
        return 'currencies';
    }

    protected function casts(): array
    {
        return [
            'conversion_rate' => 'decimal:6',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
