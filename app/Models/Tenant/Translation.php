<?php

namespace App\Models\Tenant;

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
    ];

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
