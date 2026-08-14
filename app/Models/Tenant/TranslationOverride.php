<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationOverride extends Model
{
    protected $fillable = [
        'language_id',
        'key',
        'value',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
