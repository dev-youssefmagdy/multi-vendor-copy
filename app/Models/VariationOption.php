<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\SoftDeletesCatalogRow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class VariationOption extends Model
{
    use HasFactory;
    use HasTranslations, CentralConnection, SoftDeletesCatalogRow;

    protected array $translated = ['name'];

    protected $fillable = [
        'external_id',
        'variation_id',
        'swatch',
    ];

    protected function casts(): array
    {
        return [
        ];
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_option')->withTimestamps();
    }

    /** ON DELETE actions of the FKs pointing at this table, replayed on soft delete (SoftDeletesCatalogRow). */
    protected static function catalogDeleteActions(): array
    {
        return [
            ['product_variant_option', 'variation_option_id', 'cascade'],
        ];
    }
}
