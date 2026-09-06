<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationOverride extends Model
{
    protected $fillable = [
        'language_id',
        'key',
        'key_hash',
        'value',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $override) {
            if ($override->isDirty('key')) {
                $override->key_hash = hash('sha256', (string) $override->key);
            }
        });
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
