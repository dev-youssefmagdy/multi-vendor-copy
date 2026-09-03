<?php

use App\Models\Tenant\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $defaults = [
            'logo_bg_color' => '#ffffff',
            'logo_shape' => 'rectangle',
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
            ->whereIn('name', ['logo_bg_color', 'logo_shape'])
            ->where('group', 'appearance')
            ->delete();
    }
};
