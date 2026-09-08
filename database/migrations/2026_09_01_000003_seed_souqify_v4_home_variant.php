<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'souqify', 'key' => 'v4'],
            [
                'name' => 'Green Edition',
                'description' => 'Souqify home page based on the souqify-3 green mockup.',
                'sections' => null,
                'colors' => null,
                'view' => 'themes.souqify.pages.home-v4',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'souqify')->where('key', 'v4')->delete();
    }
};
