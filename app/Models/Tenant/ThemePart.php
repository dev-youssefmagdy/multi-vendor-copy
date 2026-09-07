<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemePart extends Model
{
    use HasFactory;

    public const TYPES = ['header', 'footer'];

    protected $fillable = [
        'theme_id',
        'central_part_id',
        'type',
        'name',
        'slug',
        'content',
        'image',
        'is_active',
        'is_default',
        'is_customized',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_customized' => 'boolean',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
