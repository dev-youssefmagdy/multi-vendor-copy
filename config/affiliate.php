<?php

// Defaults for the affiliate program. Admin overrides are stored in `app_settings`
// (see App\Support\AffiliateSettings) and take precedence over these values.
return [
    'cookie_days'              => (int) env('AFFILIATE_COOKIE_DAYS', 90),
    'default_commission_type'  => env('AFFILIATE_DEFAULT_COMMISSION_TYPE', 'percentage'),
    'default_commission_value' => (float) env('AFFILIATE_DEFAULT_COMMISSION_VALUE', 10),
    'min_payout'               => (float) env('AFFILIATE_MIN_PAYOUT', 0),
];
