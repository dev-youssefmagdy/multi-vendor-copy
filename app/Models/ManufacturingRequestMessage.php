<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ManufacturingRequestMessage extends Model
{
    use CentralConnection;

    protected $fillable = [
        'manufacturing_request_id',
        'sender_type',
        'sender_name',
        'message',
    ];

    public function manufacturingRequest(): BelongsTo
    {
        return $this->belongsTo(ManufacturingRequest::class);
    }
}
