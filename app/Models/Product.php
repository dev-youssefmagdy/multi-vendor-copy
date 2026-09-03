<?php

namespace App\Models;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductTenantAssignment;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Product extends Model
{
    use HasFactory;
    use HasTranslations, CentralConnection, SoftDeletes;

    protected array $translated = ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'];

    protected $with = ['files'];

    protected $fillable = [
        'external_id',
        'slug',
        'sku',
        'status',
        'delivery_scope',
        'base_price',
        'sale_price',
        'cost_price',
        'stock',
        'min_stock',
        'manage_stock',
        'sold_count',
        'is_taxable',
        'requires_shipping',
        'weight_grams',
        'fixed_shipping_costs',
        'factory',
        'published_at',
    ];

    protected $appends = [
        'primary_image_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'delivery_scope' => DeliveryScope::class,
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'manage_stock' => 'boolean',
            'sold_count' => 'integer',
            'is_taxable' => 'boolean',
            'requires_shipping' => 'boolean',
            'weight_grams' => 'integer',
            'fixed_shipping_costs' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Resolve the fixed shipping cost for a given country from this product's
     * per-product JSON column. Returns null if no entry exists for that country.
     */
    public function fixedShippingCostForCountry(int $countryId): ?float
    {
        if ($countryId <= 0) {
            return null;
        }
        $costs = $this->fixed_shipping_costs ?? [];
        $key = (string) $countryId;

        return array_key_exists($key, $costs) ? (float) $costs[$key] : null;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    function badges(): BelongsToMany
    {
        return $this->belongsToMany(ProductBadge::class, 'product_badge_product')->withTimestamps();
    }

    public function shippingZones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class)->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_products')
            ->withPivot(['variation_id', 'quantity'])
            ->withTimestamps();
    }

    public function branchProducts(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class)->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        // $file = $this->files->count() > 0
        //     ? $this->files->firstWhere('key', 'primary_medium') ?? $this->files->firstWhere('key', 'primary_original')
        //     : $this->files->whereIn('key', ['primary_medium', 'primary_original', 'gallery'])->first();

        $file = $this->files->whereIn('key', ['primary_medium', 'primary_original', 'gallery'])->first();
        return $file?->full_path;
    }

    public function tenantAssignments(): HasMany
    {
        return $this->hasMany(ProductTenantAssignment::class);
    }

    /**
     * Return all active FixedShippingCosts with their country.
     * Used in the vendor admin product view to list per-country shipping rates.
     */
    public function applicableFixedShippingCosts(): \Illuminate\Database\Eloquent\Collection
    {
        return FixedShippingCost::query()
            ->with('country')
            ->active()
            ->orderBy('country_id')
            ->get();
    }

    /**
     * Whether this product should be shown in tenant panels/storefronts.
     * Draft/archived or soft-deleted products are hidden until the admin
     * restores them or sets the status back to Published.
     */
    public function isVisibleToTenants(): bool
    {
        return $this->status === ProductStatus::Published && !$this->trashed();
    }
}
