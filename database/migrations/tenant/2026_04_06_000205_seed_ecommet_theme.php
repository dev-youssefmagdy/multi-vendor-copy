<?php

use App\Models\Tenant\Theme;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Remove any placeholder/unnamed themes and ensure ecommet is the only active theme.
        Theme::query()->delete();

        // Theme::query()->create([
        //     'central_theme_id' => null,
        //     'name' => 'Ecommet',
        //     'slug' => 'ecommet',
        //     'is_active' => true,
        //     'preview_path' => 'ecommet/assets/images/home/landingImg.png',
        // ]);
    }

    public function down(): void
    {
        Theme::query()->where('slug', 'ecommet')->delete();
    }
};
