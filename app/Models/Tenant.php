<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Models\Tenant\Setting;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    // No physical columns other than `id`/`data` exist on this table (see
    // vendor/stancl/virtualcolumn) — every other attribute, including the
    // many dynamic `compliance_*`/`shop_name`/etc keys saved via saveData(),
    // is redirected into the `data` JSON column regardless of name. A fixed
    // $fillable allowlist here would silently drop any key not listed, so
    // mass assignment stays wide open like the base Stancl\Tenancy model.
    protected $guarded = [];

    // `category_ids` is deliberately NOT cast to 'array' here: this model routes
    // every non-physical attribute through vendor/stancl/virtualcolumn, which
    // stores plain PHP values inside the `data` JSON column and JSON-encodes
    // that whole column exactly once on save. Declaring an Eloquent 'array'
    // cast on top of that made every load+save cycle json_encode() the value
    // an extra time (VirtualColumn's setAttribute always re-encodes, even for
    // already-encoded input), snowballing into deeply nested escaped JSON
    // strings after a few cycles. getCategoryIdsAttribute() below both reads
    // plain arrays correctly and unwraps any such legacy corrupted value.
    protected $casts = [
        'status' => TenantStatus::class,
        'trial_ends_at' => 'datetime',
        'activated_at' => 'datetime',
        'compliance_accepted_at' => 'datetime',
        'compliance_reviewed_at' => 'datetime',
        'profit_percentage' => 'decimal:2',
        'data' => 'array',
        'launch_ready' => 'boolean',
    ];

    /**
     * @return int[]
     */
    public function getCategoryIdsAttribute($value = null): array
    {
        $raw = $value ?? ($this->attributes['category_ids'] ?? null);

        while (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $raw = $decoded;
        }

        return is_array($raw) ? array_values(array_map('intval', $raw)) : [];
    }

    public function isLaunchReady(): bool
    {
        return (bool) ($this->launch_ready ?? false);
    }


    public function primaryLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'primary_language_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'tenant_id', 'id');
    }

    public function tenantCountries(): HasMany
    {
        return $this->hasMany(TenantCountry::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id', 'id');
    }

    static function saveData($tenantId, $data): void
    {
        tenancy()->central(function () use ($tenantId, $data) {
            $tenant = self::query()
                ->where('id', $tenantId)->first();

            $tenant->fill($data);
            $tenant->save();
        });
    }

    function getLogoAttribute()
    {
        tenancy()->initialize($this);
        $setting = Setting::query()->where('name', 'logo_path_en')->value('value')
            ?: Setting::query()->where('name', 'logo_path_ar')->value('value');
        tenancy()->end();
        return $setting ? $setting : null;
    }
}
