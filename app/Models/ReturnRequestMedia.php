<?php

namespace App\Models;

use App\Enums\ReturnMediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ReturnRequestMedia extends Model
{
    use CentralConnection;

    protected $fillable = [
        'return_request_id',
        'file_path',
        'type',
    ];

    protected $casts = [
        'type' => ReturnMediaType::class,
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function url(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
