<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeVariant::query()->updateOrCreate(
            ['theme_slug' => 'souqify', 'key' => 'v6'],
            [
                'name' => 'Pink Edition',
                'description' => 'Souqify home page based on the souqify-5 pink mockup.',
                'sections' => ['hero', 'trust_bar', 'browse_categories', 'flash_sale', 'new_arrivals', 'trending_this_week', 'explore_products', 'top_products', 'recommended_products'],
                'colors' => null,
                'view' => 'themes.souqify.pages.home-v6',
                'is_default' => false,
                'is_active' => true,
                'preview_image' => null,
            ]
        );
    }

    public function down(): void
    {
        HomeVariant::query()->where('theme_slug', 'souqify')->where('key', 'v6')->delete();
    }
};
