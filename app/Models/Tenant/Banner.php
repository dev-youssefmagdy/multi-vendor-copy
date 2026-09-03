<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasTranslations;

    protected array $translated = ['title', 'subtitle', 'button_text'];

    protected $fillable = [
        'url',
        'image_path',
        'serial_number',
    ];
}
