<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantAiTranslationPurchase extends Model
{
    use CentralConnection;

    protected $table = 'tenant_ai_translation_purchases';

    protected $fillable = [
        'tenant_id',
        'central_language_id',
        'amount',
        'gateway_code',
        'transaction_uuid',
        'translated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'translated_at' => 'datetime',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'central_language_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public static function hasCompleted(string $tenantId, int $centralLanguageId): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('central_language_id', $centralLanguageId)
            ->where('status', 'completed')
            ->exists();
    }
}
