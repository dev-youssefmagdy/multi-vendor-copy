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
        'is_active_for_tenants',
        'is_free',
        'default_language_id',
    ];

    protected $casts = [
        'is_active_for_tenants' => 'boolean',
        'is_free' => 'boolean',
    ];

    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }
}
