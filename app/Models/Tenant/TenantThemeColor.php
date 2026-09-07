<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant's color overrides for one home variant of a theme, optionally scoped
 * to a country. A null country_id row is the tenant-wide default for that
 * variant; a null home_variant_id row applies to a theme with no variant
 * catalog. Mirrors TenantHomeVariant; keys in `colors` are CSS custom
 * property names (e.g. "--color-primary") and only overridden keys need to
 * be present — anything missing falls back to the HomeVariant's defaults.
 */
class TenantThemeColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'home_variant_id',
        'country_id',
        'colors',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
