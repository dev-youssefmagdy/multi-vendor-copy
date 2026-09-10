<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BrandRequestMessage extends Model
{
    use CentralConnection;

    protected $fillable = [
        'brand_request_id',
        'sender_type',
        'sender_name',
        'message',
    ];

    public function brandRequest(): BelongsTo
    {
        return $this->belongsTo(BrandRequest::class);
    }
}
