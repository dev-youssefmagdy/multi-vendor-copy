<?php

namespace App\Models\Tenant;

use App\Enums\TranslationSource;
use App\Eloquent\Relations\CachedBelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'field',
        'value',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'source' => TranslationSource::class,
        ];
    }

    public function language(): CachedBelongsTo
    {
        return new CachedBelongsTo(
            (new Language())->newQuery(),
            $this,
            'language_id',
            'id',
            'language',
        );
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
