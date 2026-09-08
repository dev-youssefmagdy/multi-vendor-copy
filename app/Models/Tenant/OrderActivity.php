<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'title',
        'description',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
