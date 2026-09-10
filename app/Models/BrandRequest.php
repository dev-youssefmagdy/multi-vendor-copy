<?php

namespace App\Models;

use App\Enums\BrandRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BrandRequest extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'attachments',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'status' => BrandRequestStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BrandRequestMessage::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(BrandPaymentRequest::class);
    }
}
