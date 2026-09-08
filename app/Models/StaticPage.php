<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['title', 'slug', 'content'];

    protected $fillable = [
        'slug',
        'status',
        'is_compliance',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_compliance' => 'boolean',
        ];
    }
}
