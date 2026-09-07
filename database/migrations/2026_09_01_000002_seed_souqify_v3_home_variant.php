<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'souqify', 'key' => 'v3'],
            [
                'name' => 'Modern Edition',
                'description' => 'Souqify home page based on the souqify-2 teal mockup.',
                'sections' => null,
                'colors' => null,
                'view' => 'themes.souqify.pages.home-v3',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'souqify')->where('key', 'v3')->delete();
    }
};
