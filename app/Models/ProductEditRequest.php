<?php

namespace App\Models;

use App\Enums\ProductEditRequestStatus;
use App\Models\Concerns\HasCatalogRefs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProductEditRequest extends Model
{
    use CentralConnection;
    use HasCatalogRefs;

    protected $fillable = [
        'tenant_id',
        'product_id',
        // Dedup plan phase 5: central-mode requests (no tenant copy) are identified by the central product id.
        'product_type',
        'catalog_product_id',
        'central_product_id',
        'product_slug',
        'requested_translations',
        'current_translations',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * Dedup plan §4.2a: `central_product_id` = the central product the request is about (NULL for an own product),
     * derived from the typed pair HasCatalogRefs just filled. Guarded: no-op until the central migration ran.
     */
    protected static function booted(): void
    {
        static::saving(function (self $request) {
            if (! $request->isDirty(['product_type', 'catalog_product_id'])
                || ! \App\Support\Catalog\CatalogRefColumns::has($request->getConnection(), $request->getTable(), 'central_product_id')) {
                return;
            }

            $request->setAttribute('central_product_id', $request->getAttribute('product_type') === \App\Support\Catalog\CatalogRef::CENTRAL
                ? $request->getAttribute('catalog_product_id')
                : null);
        });
    }

    protected function casts(): array
    {
        return [
            'requested_translations' => 'array',
            'current_translations' => 'array',
            'status' => ProductEditRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', ProductEditRequestStatus::Pending);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
