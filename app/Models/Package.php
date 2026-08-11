<?php

namespace App\Models;

use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Package extends Model
{
    use HasFactory;
    use HasTranslations;
    use CentralConnection;

    protected array $translated = ['name', 'description'];

    protected $fillable = [
        'name',
        'icon',
        'status',
        'term',
        'price',
        'features',
        'categories_count',
        'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'term' => PackageTerm::class,
            'price' => 'decimal:2',
            'features' => 'array',
            'categories_count' => 'integer',
            'trial_days' => 'integer',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }


}
