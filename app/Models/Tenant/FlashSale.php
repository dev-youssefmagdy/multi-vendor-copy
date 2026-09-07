<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FlashSale extends Model
{
    use HasFactory;

    protected $appends = ['banner_url'];

    protected $fillable = [
        'central_flash_sale_id',
        'product_id',
        'discount_percentage',
        'start_date',
        'end_date',
        'active',
        'banner_image',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'active' => 'boolean',
            'banner_image' => 'string',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_product')->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function getBannerUrlAttribute(): ?string
    {
        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'banner')
            : $this->files()->where('key', 'banner')->first();

        if ($file) {
            return tenant_asset($file->path);
        }

        // Fallback: central-synced banner stored as absolute URL
        if ($this->banner_image && str_starts_with($this->banner_image, 'http')) {
            return $this->banner_image;
        }

        return null;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $flashSale) {
            $flashSale->files()->get()->each->delete();
        });
    }

    public function scopeActiveWindow($query, ?CarbonInterface $moment = null): Builder
    {
        $moment ??= Carbon::now();

        return $query
            ->where('active', true)
            ->where(fn($windowQuery) => $windowQuery
                ->whereNull('start_date')
                ->orWhere('start_date', '<=', $moment))
            ->where(fn($windowQuery) => $windowQuery
                ->whereNull('end_date')
                ->orWhere('end_date', '>=', $moment));
    }

    public function isCurrentlyActive(?CarbonInterface $moment = null): bool
    {
        $moment ??= Carbon::now();

        if (!$this->active) {
            return false;
        }

        if ($this->start_date && $this->start_date->gt($moment)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($moment)) {
            return false;
        }

        return true;
    }
}
