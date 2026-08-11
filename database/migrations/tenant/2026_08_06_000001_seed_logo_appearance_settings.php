<?php

use App\Models\Tenant\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $storeName = (string) (Setting::query()->where('name', 'store_name')->value('value') ?: '');
        $storeNameByLocale = [];

        $storeNameSetting = Setting::query()->with('translations.language')->where('name', 'store_name')->first();
        if ($storeNameSetting) {
            foreach ($storeNameSetting->translationsByLocale(['value']) as $locale => $payload) {
                $storeNameByLocale[$locale] = (string) ($payload['value'] ?? '');
            }
        }

        // `logo_path` is deprecated in favour of the per-language keys below, but
        // it is intentionally left in place so any not-yet-migrated consumer keeps
        // working; its value seeds both new paths.
        $legacyLogoPath = (string) (Setting::query()->where('name', 'logo_path')->value('value') ?: '');

        $defaults = [
            'logo_mode' => 'image',
            'logo_text_ar' => $storeNameByLocale['ar'] ?? $storeName,
            'logo_text_en' => $storeNameByLocale['en'] ?? $storeName,
            'logo_color' => '#111827',
            'logo_font_ar' => 'cairo',
            'logo_font_en' => 'poppins',
            'logo_path_ar' => $legacyLogoPath,
            'logo_path_en' => $legacyLogoPath,
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(
                ['name' => $key],
                ['value' => $value, 'group' => 'appearance']
            );
        }
    }

    public function down(): void
    {
        Setting::query()
            ->whereIn('name', [
                'logo_mode',
                'logo_text_ar',
                'logo_text_en',
                'logo_color',
                'logo_font_ar',
                'logo_font_en',
                'logo_path_ar',
                'logo_path_en',
            ])
            ->where('group', 'appearance')
            ->delete();
    }
};
