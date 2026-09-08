<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ReturnRequestNote extends Model
{
    use CentralConnection;

    public const AUTHOR_ADMIN = 'admin';
    public const AUTHOR_TENANT = 'tenant';
    public const AUTHOR_SYSTEM = 'system';
    public const AUTHOR_CUSTOMER = 'customer';

    protected $fillable = [
        'return_request_id',
        'author_type',
        'author_id',
        'note',
        'customer_visible',
    ];

    protected $casts = [
        'customer_visible' => 'boolean',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }
}
