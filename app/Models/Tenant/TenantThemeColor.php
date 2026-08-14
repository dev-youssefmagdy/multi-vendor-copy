<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant's override of a theme color CSS variable.
 * Lives in the tenant database (one row per theme_id + variable_key), so no
 * separate tenant_id column is needed — mirrors TenantPageSection.
 */
class TenantThemeColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'variable_key',
        'value',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
