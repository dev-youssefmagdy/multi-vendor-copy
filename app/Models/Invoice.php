<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Invoice extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'tenant_id',
        'tenant_order_id',
        'order_uuid',
        'order_number',
        'invoice_number',
        'customer_name',
        'customer_email',
        'currency',
        'amount',
        'status',
        'payment_method',
        'payment_reference',
        'pdf_disk',
        'pdf_path',
        'meta',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount' => 'decimal:2',
            'meta' => 'array',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        if (($this->pdf_disk ?: 'public') !== 'public') {
            return null;
        }
        $tenant = $this->tenant;
        if (!$tenant) {
            return null;
        }
        tenancy()->initialize($this->tenant_id);
        $path = tenant_asset($this->pdf_path);
        $path = str_replace(config('app.url'), tenant()->domains()->first()->domain, $path);
        // check if not exists http:// or https:// in the path, if not, prepend the app url
        if (!str_contains($path, 'http://') && !str_contains($path, 'https://')) {
            $path = 'http://' . $path;
        }
        tenancy()->end();
        return $path;
    }
}
