<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShippingStop extends Model
{
    protected $fillable = [
        'from_date',
        'to_date',
        'reason',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'active' => 'boolean',
        ];
    }

    /**
     * Scope: stops that overlap with [windowStart, windowEnd].
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $windowStart, CarbonInterface $windowEnd): Builder
    {
        return $query
            ->where('active', true)
            ->where('from_date', '<=', $windowEnd->toDateString())
            ->where('to_date', '>=', $windowStart->toDateString());
    }

    /**
     * Count calendar days that fall within this stop period.
     */
    public function daysCount(): int
    {
        return (int) $this->from_date->diffInDays($this->to_date) + 1;
    }

    /**
     * Count days this stop overlaps with the given window.
     */
    public function overlapDays(CarbonInterface $windowStart, CarbonInterface $windowEnd): int
    {
        $from = $this->from_date->max($windowStart);
        $to = $this->to_date->min($windowEnd);

        if ($from->gt($to)) {
            return 0;
        }

        return (int) $from->diffInDays($to) + 1;
    }
}
