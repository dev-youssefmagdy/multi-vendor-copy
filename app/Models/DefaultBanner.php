<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class DefaultBanner extends Model
{
    use CentralConnection;
    use HasTranslations;

    protected array $translated = ['title', 'subtitle', 'button_text'];

    protected $fillable = [
        'url',
        'image_path',
        'serial_number',
    ];
}
