<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\SocialMediaIconEnum;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = [
        'icon',
        'url',
        'serial_number',
    ];

    protected function casts(): array
    {
        return [
            'icon' => SocialMediaIconEnum::class,
        ];
    }
}
