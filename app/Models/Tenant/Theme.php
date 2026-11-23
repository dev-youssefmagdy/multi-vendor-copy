<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant\Concerns\OverlaysCentralConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use OverlaysCentralConfig;
    use HasFactory;

    protected $fillable = [
        'central_theme_id',
        'name',
        'slug',
        'is_active',
        'is_universal',
        'preview_path',
    ];

    protected static function configCatalogSection(): string
    {
        return 'themes';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_universal' => 'boolean',
        ];
    }

    public function countries(): HasMany
    {
        return $this->hasMany(ThemeCountry::class)->orderBy('name');
    }
}
