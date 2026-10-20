<?php

namespace App\Support;

use App\Enums\AppSettingType;
use App\Models\AppSetting;

/**
 * Admin-editable affiliate program settings (stored in `app_settings`,
 * falling back to config/affiliate.php).
 */
class AffiliateSettings
{
    private const PREFIX = 'affiliate_';

    public static function cookieDays(): int
    {
        return max(1, (int) self::get('cookie_days'));
    }

    public static function defaultCommissionType(): string
    {
        $type = (string) self::get('default_commission_type');

        return in_array($type, ['percentage', 'fixed'], true) ? $type : 'percentage';
    }

    public static function defaultCommissionValue(): float
    {
        return max(0.0, (float) self::get('default_commission_value'));
    }

    public static function minPayout(): float
    {
        return max(0.0, (float) self::get('min_payout'));
    }

    public static function all(): array
    {
        return [
            'cookie_days'              => self::cookieDays(),
            'default_commission_type'  => self::defaultCommissionType(),
            'default_commission_value' => self::defaultCommissionValue(),
            'min_payout'               => self::minPayout(),
        ];
    }

    public static function save(array $values): void
    {
        foreach (array_intersect_key($values, config('affiliate')) as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => self::PREFIX . $key],
                [
                    'value'     => (string) $value,
                    'type'      => AppSettingType::String,
                    'is_public' => false,
                ]
            );
        }
    }

    private static function get(string $key): mixed
    {
        $stored = AppSetting::query()->where('key', self::PREFIX . $key)->value('value');

        return $stored ?? config('affiliate.' . $key);
    }
}
