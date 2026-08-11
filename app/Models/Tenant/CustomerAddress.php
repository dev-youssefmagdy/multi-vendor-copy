<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'label',
        'full_name',
        'email',
        'phone',
        'address_line_1',
        'city',
        'state',
        'country',
        'country_id',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * One-liner summary, e.g. "New Cairo, Cairo, Egypt"
     */
    public function getOnelinerAttribute(): string
    {
        return collect([$this->address_line_1, $this->city, $this->state, $this->country])
            ->filter()
            ->implode(', ');
    }
}
