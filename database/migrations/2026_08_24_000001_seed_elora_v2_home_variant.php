<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'elora', 'key' => 'v2'],
            [
                'name' => 'Purple Edition',
                'description' => 'Elora home page with a bold purple & dark color scheme.',
                'sections' => null,
                'colors' => null,
                'view' => 'themes.elora.pages.home-v2',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'elora')->where('key', 'v2')->delete();
    }
};
