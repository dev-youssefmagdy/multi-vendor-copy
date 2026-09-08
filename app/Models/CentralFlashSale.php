<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CentralFlashSale extends Model
{
    protected $table = 'central_flash_sales';

    protected $appends = ['banner_url'];

    protected $fillable = [
        'discount_percentage',
        'start_date',
        'end_date',
        'active',
        'banner_image',
        'country_id',
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'central_flash_sale_product',
            'central_flash_sale_id',
            'product_id'
        )->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if ($this->banner_image) {
            return asset('storage/' . $this->banner_image);
        }

        $file = $this->relationLoaded('files')
            ? $this->files->firstWhere('key', 'banner')
            : $this->files()->where('key', 'banner')->first();

        return $file ? asset('storage/' . $file->path) : null;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $flashSale) {
            $flashSale->files()->get()->each->delete();
        });
    }

    public function scopeActiveWindow(Builder $query, ?CarbonInterface $moment = null): Builder
    {
        $moment ??= Carbon::now();

        return $query
            ->where('active', true)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $moment))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $moment));
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
