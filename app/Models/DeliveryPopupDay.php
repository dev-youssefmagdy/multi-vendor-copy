<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPopupDay extends Model
{
    protected $fillable = [
        'country_id',
        'day_number',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'day_number' => 'integer',
            'percentage' => 'decimal:2',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
