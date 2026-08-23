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
        'ai_translation_price',
        'countries',
        'image_file_id',
        'translation_progress',
        'sort_order',
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
            'ai_translation_price' => 'decimal:2',
            'countries' => 'array',
            'translation_progress' => 'integer',
            'sort_order' => 'integer',
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

    /** True when AI translation is offered for this language (price set, even if 0). */
    public function offersAiTranslation(): bool
    {
        return $this->ai_translation_price !== null;
    }

    /** True when AI translation is free (price = 0). */
    public function aiTranslationIsFree(): bool
    {
        return $this->ai_translation_price !== null && (float) $this->ai_translation_price === 0.0;
    }
}
