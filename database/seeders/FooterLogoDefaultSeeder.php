<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant\Setting;
use Illuminate\Database\Seeder;

/**
 * Backfills all footer logo settings for existing tenants by copying the
 * current header logo values so the footer continues to look the same.
 *
 * Run once per tenant:
 *   php artisan tenants:run db:seed --option="class=FooterLogoDefaultSeeder"
 */
class FooterLogoDefaultSeeder extends Seeder
{
    /** Header key => footer key */
    private const MAP = [
        'logo_mode'    => 'footer_logo_mode',
        'logo_text_ar' => 'footer_logo_text_ar',
        'logo_text_en' => 'footer_logo_text_en',
        'logo_color'   => 'footer_logo_color',
        'logo_bg_color'=> 'footer_logo_bg_color',
        'logo_shape'   => 'footer_logo_shape',
        'logo_font_ar' => 'footer_logo_font_ar',
        'logo_font_en' => 'footer_logo_font_en',
        'logo_path_ar' => 'footer_logo_path_ar',
        'logo_path_en' => 'footer_logo_path_en',
    ];

    public function run(): void
    {
        $headerValues = Setting::query()
            ->whereIn('name', array_keys(self::MAP))
            ->pluck('value', 'name');

        foreach (self::MAP as $headerKey => $footerKey) {
            $alreadySet = Setting::query()
                ->where('name', $footerKey)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->exists();

            if ($alreadySet) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['name' => $footerKey],
                ['value' => (string) ($headerValues[$headerKey] ?? ''), 'group' => 'appearance']
            );
        }

        // Seed width as empty (theme default) if not already set.
        Setting::query()->updateOrCreate(
            ['name' => 'footer_logo_width'],
            ['value' => '', 'group' => 'appearance']
        );
    }
}
