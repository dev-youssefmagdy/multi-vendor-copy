<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Category extends Model
{
    use HasFactory;
    use HasTranslations, CentralConnection;

    protected array $translated = ['name', 'slug', 'description', 'meta_keywords', 'meta_description'];

    protected $connection = 'mysql';

    protected $fillable = [
        'external_id',
        'parent_id',
        'status',
        'is_featured',
    ];

    protected $appends = ['thumb_url'];

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function getThumbUrlAttribute(): ?string
    {
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'thumb')
            : $this->files()->where('key', 'thumb')->first();

        return $file?->full_path;
    }
}
