<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['name', 'slug', 'description', 'meta_keywords', 'meta_description'];

    protected $fillable = [
        'central_category_id',
        'parent_id',
        'order_number',
        'active',
        'featured',
    ];

    protected $appends = ['thumb_url'];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
            'active' => 'boolean',
            'featured' => 'boolean',
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

    public function getAllChildrenIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        return $ids;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    function centralCategory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class, 'central_category_id');
    }

    public function getThumbUrlAttribute(): ?string
    {
        // Prefer the tenant-uploaded thumb; fall back to the central
        // category's thumb when the tenant has not uploaded one.
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'thumb')
            : $this->files()->where('key', 'thumb')->first();

        if ($file?->full_path) {
            return $file->full_path;
        }

        $centralUrl = $this->centralCategory?->thumb_url;
        if ($centralUrl) {
            return $centralUrl;
        }

        return asset('elora/assets/images/category-default.png');
    }
}
