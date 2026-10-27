<?php

namespace App\Models\Tenant;

use App\Models\Concerns\HasCountries;
use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasCountries;
    use HasTranslations;

    protected string $countryPivotTable = 'banner_country';

    protected ?string $countryCacheTag = 'Banner';

    protected array $translated = ['title', 'subtitle', 'button_text'];

    protected $fillable = [
        'url',
        'image_path',
        'serial_number',
    ];
}
