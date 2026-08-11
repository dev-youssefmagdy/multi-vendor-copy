<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Currency extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'code',
        'name',
        'sign',
        'conversion_rate',
    ];

    protected function casts(): array
    {
        return [
            'conversion_rate' => 'decimal:6',
        ];
    }
}
