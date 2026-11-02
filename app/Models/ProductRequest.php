<?php

namespace App\Models;

use App\Enums\ProductRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'product_url',
        'attachments',
        'status',
        'tenant_has_unread',
        'admin_has_unread',
        'last_reply_at',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'attachments'       => 'array',
            'status'            => ProductRequestStatus::class,
            'tenant_has_unread' => 'boolean',
            'admin_has_unread'  => 'boolean',
            'last_reply_at'     => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function messages(): HasMany
    {
        return $this->hasMany(ProductRequestMessage::class)->orderBy('created_at');
    }

    /** PANELS-UPDATES-PRODUCT-REQUESTS: the tenant (store) that submitted the request. */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * PANELS-UPDATES-PRODUCT-REQUESTS: short, sequential, readable reference (PR-00042) built from the
     * auto-increment id, shown everywhere instead of a raw "#42" or the tenant UUID.
     */
    public function getCodeAttribute(): string
    {
        return static::codeFor((int) $this->id);
    }

    public static function codeFor(int $id): string
    {
        return 'PR-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    /** Store name shown to admins; falls back to the tenant id when the store has no name yet. */
    public function storeName(): string
    {
        $tenant = $this->relationLoaded('tenant') ? $this->tenant : $this->tenant()->first();

        return (string) ($tenant?->getAttribute('shop_name') ?: $tenant?->getAttribute('name') ?: $this->tenant_id);
    }

    /**
     * PANELS-UPDATES-PRODUCT-REQUESTS: free-text search over reference code (PR-00042 or just 42), title,
     * description, tenant id and the STORE NAME.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $digits = preg_match('/^(?:pr-?)?0*(\d+)$/i', $term, $m) ? (int) $m[1] : null;

        $tenantIds = Tenant::query()
            ->where(fn ($t) => $t->where('data->shop_name', 'like', "%{$term}%")->orWhere('data->name', 'like', "%{$term}%"))
            ->pluck('id')
            ->all();

        return $query->where(function (Builder $q) use ($term, $digits, $tenantIds) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('tenant_id', 'like', "%{$term}%");

            if ($digits !== null) {
                $q->orWhere('id', $digits);
            }

            if ($tenantIds !== []) {
                $q->orWhereIn('tenant_id', $tenantIds);
            }
        });
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ProductRequestStatus::Completed->value,
            ProductRequestStatus::Rejected->value,
            ProductRequestStatus::Archived->value,
        ]);
    }
}
