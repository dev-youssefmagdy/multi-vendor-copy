<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        HomeVariant::query()->firstOrCreate(
            ['theme_slug' => 'custom', 'key' => 'default'],
            [
                'name' => 'My Uploaded Theme',
                'description' => 'Your own uploaded storefront design.',
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'custom')->delete();
    }
};
