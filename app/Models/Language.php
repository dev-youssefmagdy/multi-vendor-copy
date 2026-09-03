<?php

namespace App\Models;

use App\Enums\LanguageDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\File;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Language extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'name',
        'code',
        'native_name',
        'direction',
        'is_default',
        'is_active',
        'is_free',
        'price',
        'countries',
        'image_file_id',
        'translation_progress',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'direction' => LanguageDirection::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_free' => 'boolean',
            'price' => 'decimal:2',
            'countries' => 'array',
            'translation_progress' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageFile?->full_path;
    }
}
