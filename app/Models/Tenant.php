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

    protected $fillable = [
        'id',
        'name',
        'slug',
        'email',
        'phone',
        'status',
        'category_ids',
        'primary_language_id',
        'package_id',
        'trial_ends_at',
        'activated_at',
        'profit_percentage',
        'data',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'trial_ends_at' => 'datetime',
        'activated_at' => 'datetime',
        'compliance_accepted_at' => 'datetime',
        'profit_percentage' => 'decimal:2',
        'category_ids' => 'array',
        'data' => 'array',
    ];


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

            $tenant->compliance_version = $data['compliance_version'] ?? null;
            $tenant->compliance_accepted_at = $data['compliance_accepted_at'] ?? null;
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
