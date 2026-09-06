<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'central_theme_id',
        'name',
        'slug',
        'is_active',
        'is_universal',
        'preview_path',
    ];

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
