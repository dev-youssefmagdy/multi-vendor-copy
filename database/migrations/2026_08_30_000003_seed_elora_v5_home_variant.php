<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'elora', 'key' => 'v5'],
            [
                'name' => 'Minimal Edition',
                'description' => 'Elora home page variant based on the elora-5 design.',
                'sections' => null,
                'colors' => null,
                'view' => 'themes.elora.pages.home-v5',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'elora')->where('key', 'v5')->delete();
    }
};
