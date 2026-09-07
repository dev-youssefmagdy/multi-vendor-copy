<?php

namespace App\Models\Tenant;

use App\Enums\LanguageDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant\File as TenantFile;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'central_language_id',
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
        'is_default',
        'image_file_id',
        'sort_order',
        'last_translation_token_count',
        'last_translation_cost_usd',
    ];

    protected $appends = ['image_url'];

    // boot method to set default language
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Language $language) {
            if ($language->is_default) {
                // set into cache key "default_language_code" the code of the default language with rememberForever
                $tenantId = tenant('id');
                Cache::driver('file')->forget("default_language_code_{$tenantId}");
                Cache::driver('file')->rememberForever("default_language_code_{$tenantId}", function () use ($language) {
                    return $language->code;
                });
            }
        });

        static::updating(function (Language $language) {
            if ($language->is_default) {
                // Unset default from other languages
                self::query()->where('id', '!=', $language->id)->where('is_default', true)->update(['is_default' => false]);
                // update cache key "default_language_code" with the new default language code with rememberForever
                $tenantId = tenant('id');
                Cache::driver('file')->forget("default_language_code_{$tenantId}");
                Cache::driver('file')->rememberForever("default_language_code_{$tenantId}", function () use ($language) {
                    return $language->code;
                });
            }
        });
    }

    protected function casts(): array
    {
        return [
            'direction' => LanguageDirection::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'last_translation_token_count' => 'integer',
            'last_translation_cost_usd' => 'decimal:4',
        ];
    }

    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(TenantFile::class, 'image_file_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageFile?->full_path;
    }
}
