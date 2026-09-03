<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Country extends Model
{
    use HasFactory, HasTranslations, CentralConnection;

    protected array $translated = ['name'];

    protected $fillable = [
        'iso2',
        'iso3',
        'name',
        'currency_code',
        'language_code',
        'language_direction',
        'phone_code',
        'flag_emoji',
        'default_language_id',
    ];

    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }
}
