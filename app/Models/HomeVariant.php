<?php

namespace App\Models;

use App\Models\BladeTheme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-managed catalog of homepage layout variants for a theme.
 * A variant bundles a home section order and/or a color palette; tenants
 * pick one per theme (optionally per country) from the tenant panel.
 */
class HomeVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_slug',
        'key',
        'name',
        'description',
        'preview_image',
        'sections',
        'colors',
        'view',
        'is_default',
        'is_active',
        'blade_theme_id',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'colors' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForTheme($query, string $slug)
    {
        return $query->where('theme_slug', $slug);
    }

    public function bladeTheme(): BelongsTo
    {
        return $this->belongsTo(BladeTheme::class);
    }
}
