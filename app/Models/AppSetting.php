<?php

namespace App\Models;

use App\Enums\AppSettingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    protected $fillable = [
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'type' => AppSettingType::class,
        ];
    }
}
