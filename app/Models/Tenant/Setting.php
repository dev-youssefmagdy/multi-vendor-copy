<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\SettingType;
use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['title', 'value'];

    protected $fillable = [
        'name',
        'value',
        'type',
        'options',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'options' => 'array',
        ];
    }
}
