<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\SettingType;
use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant\Concerns\OverlaysCentralConfig;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    use HasTranslations;
    use OverlaysCentralConfig;

    protected array $translated = ['title', 'value'];

    protected $fillable = [
        'name',
        'value',
        'type',
        'options',
        'group',
    ];

    protected static function configCatalogSection(): string
    {
        return 'settings';
    }

    /** Dedup plan phase 6: a vendor write wins over central's value from now on (marker, see ConfigCatalog). */
    protected static function booted(): void
    {
        static::saving(function (Setting $setting): void {
            if ($setting->isDirty('value') && ConfigCatalog::enabled('settings')) {
                $setting->setAttribute('central_overridden', 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'options' => 'array',
        ];
    }
}
