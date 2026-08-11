<?php

use App\Models\Tenant\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $shopName = (string) (Setting::query()->where('name', 'store_name')->value('value') ?: 'My Store');

        $defaults = [
            'store_name' => $shopName,
            'logo_path' => '',
            'footer_text' => '',
            'footer_copyright' => '© ' . date('Y') . ' ' . $shopName . '. All rights reserved.',
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
            ->whereIn('name', ['store_name', 'logo_path', 'footer_text', 'footer_copyright'])
            ->where('group', 'appearance')
            ->delete();
    }
};
