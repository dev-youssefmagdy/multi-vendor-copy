<?php

namespace App\Models;

use App\Enums\VariationStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\SoftDeletesCatalogRow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Variation extends Model
{
    use HasFactory;
    use HasTranslations, CentralConnection, SoftDeletesCatalogRow;

    protected array $translated = ['name', 'description'];

    protected $fillable = [
        'slug',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => VariationStatus::class,
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariationOption::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /** ON DELETE actions of the FKs pointing at this table, replayed on soft delete (SoftDeletesCatalogRow). */
    protected static function catalogDeleteActions(): array
    {
        return [
            ['product_variation', 'variation_id', 'cascade'],
            ['branch_products', 'variation_id', 'null'],
        ];
    }
}
