<?php

namespace App\Models;

use App\Enums\DomainRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class DomainRequest extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'requested_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DomainRequestStatus::class,
            'requested_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
