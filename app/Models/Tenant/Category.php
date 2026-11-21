<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    /** Global scope hiding categories central unpublished (dedup plan R1), same idea as Tenant\Product. */
    public const CENTRAL_VISIBLE_SCOPE = 'centralVisible';

    /** @var array<string, bool> per tenant database: does `categories.central_visible` exist yet? */
    protected static array $centralVisibleColumn = [];

    protected static function booted(): void
    {
        // `active` is the vendor's toggle, `central_visible` is central's publish state (synced). Tenant-created
        // categories keep the default 1. Guarded so code deployed before `tenants:migrate` keeps working.
        static::addGlobalScope(self::CENTRAL_VISIBLE_SCOPE, function (Builder $builder) {
            if (static::hasCentralVisibleColumn()) {
                $builder->where($builder->qualifyColumn('central_visible'), true);
            }
        });
    }

    public static function hasCentralVisibleColumn(): bool
    {
        $connection = (new static())->getConnection();
        $key = $connection->getName() . ':' . $connection->getDatabaseName();

        return static::$centralVisibleColumn[$key] ??= Schema::connection($connection->getName())->hasColumn('categories', 'central_visible');
    }

    protected array $translated = ['name', 'slug', 'description', 'meta_keywords', 'meta_description'];

    protected $fillable = [
        'central_category_id',
        'parent_id',
        'order_number',
        'active',
        'central_visible',
        'featured',
    ];

    protected $appends = ['thumb_url'];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
            'active' => 'boolean',
            'central_visible' => 'boolean',
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
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
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
