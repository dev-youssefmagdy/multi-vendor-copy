<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant's color overrides for a theme's active variant, optionally scoped to
 * a country. A null country_id row is the tenant-wide default for the theme.
 * Mirrors TenantHomeVariant; keys in `colors` are CSS custom property names
 * (e.g. "--color-primary") and only overridden keys need to be present —
 * anything missing falls back to the active HomeVariant's default colors.
 */
class TenantThemeColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
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
