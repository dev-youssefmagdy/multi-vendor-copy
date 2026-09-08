<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['title', 'body'];

    protected $fillable = [
        'slug',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
