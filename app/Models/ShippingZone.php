<?php

namespace App\Models;

use App\Enums\ShippingZoneStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ShippingZone extends Model
{
    use HasFactory;
    use HasTranslations, CentralConnection;

    protected array $translated = ['name', 'description'];

    protected $fillable = [
        'branch_id',
        'country_id',
        'name',
        'code',
        'currency_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShippingZoneStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingZoneRate::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }
}
