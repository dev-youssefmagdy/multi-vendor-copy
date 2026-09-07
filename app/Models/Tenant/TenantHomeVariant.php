<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant's chosen home page variant for a theme, optionally scoped to a
 * country. A null country_id row is the tenant-wide default for the theme.
 * Lives in the tenant database, mirrors TenantThemeColor/ThemeCountry.
 */
class TenantHomeVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'country_id',
        'home_variant_id',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
