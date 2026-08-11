<?php

namespace App\Services\Tenant;

use App\Models\ShippingDayRule;
use App\Models\Tenant\Setting;
use Carbon\Carbon;

class ShippingEstimateService
{
    /**
     * Compute the full shipping estimate starting from $orderDate.
     *
     * Pass an ISO-2 $countryCode to get the country-specific min/max days;
     * omit it (or pass null) to fall back to the global default rule.
     *
     * Returns an array:
     *   base_days         – min shipping days from the matching rule (or legacy setting)
     *   max_days          – max shipping days from the matching rule
     *   stop_days         – extra days due to active stop periods
     *   total_days        – base_days + stop_days
     *   estimated_arrival – Carbon date based on base_days + stop_days
     *   stops             – array of overlapping stop records
     */
    public function estimate(?Carbon $orderDate = null, ?string $countryCode = null): array
    {
        $orderDate ??= Carbon::now();

        // --- Resolve base / max days from central per-country rule -----------
        $rule = ShippingDayRule::forCountry($countryCode);

        if ($rule) {
            $baseDays = $rule->min_days;
            $maxDays = $rule->max_days;
        } else {
            // Legacy fallback: tenant setting (keeps backward-compat)
            $baseDays = (int) (Setting::query()->where('name', 'default_shipping_days')->value('value') ?? 3);
            $maxDays = $baseDays + 4;
        }

        // --- Stop-period calculation (unchanged) ----------------------------
        $stopsJson = Setting::query()->where('name', 'shipping_stop_periods')->value('value') ?? '[]';
        $stops = json_decode($stopsJson, true) ?? [];

        $deliveryStart = $orderDate->copy();
        $deliveryEnd = $orderDate->copy()->addDays($baseDays);

        $overlappingStops = [];
        $extraDays = 0;

        foreach ($stops as $stop) {
            $fromDate = Carbon::parse($stop['from']);
            $toDate = Carbon::parse($stop['to']);

            if ($fromDate->lte($deliveryEnd) && $toDate->gte($deliveryStart)) {
                $overlap = (int) $fromDate->max($deliveryStart)->diffInDays($toDate->min($deliveryEnd)) + 1;
                $extraDays += $overlap;
                $overlappingStops[] = [
                    'from' => $fromDate->toDateString(),
                    'to' => $toDate->toDateString(),
                    'reason' => $stop['reason'] ?? null,
                    'extra_days' => $overlap,
                ];
            }
        }

        $totalDays = $baseDays + $extraDays;
        $estimatedArrival = $orderDate->copy()->addDays($totalDays);

        return [
            'base_days' => $baseDays,
            'max_days' => $maxDays,
            'stop_days' => $extraDays,
            'total_days' => $totalDays,
            'estimated_arrival' => $estimatedArrival,
            'stops' => $overlappingStops,
        ];
    }
}
