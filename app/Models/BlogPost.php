<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BlogPost extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['title', 'slug', 'excerpt', 'content'];

    protected $fillable = [
        'blog_category_id',
        'slug',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function getImageUrlAttribute(): ?string
    {
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'image')
            : $this->files()->where('key', 'image')->first();

        return $file?->full_path;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $post) {
            $post->files()->get()->each->delete();
        });
    }
}
