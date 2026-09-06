<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'souqify', 'key' => 'v2'],
            [
                'name' => 'Purple Edition',
                'description' => 'Souqify home page with a bold purple color scheme.',
                'sections' => null,
                'colors' => null,
                'view' => 'themes.souqify.pages.home-v2',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'souqify')->where('key', 'v2')->delete();
    }
};
