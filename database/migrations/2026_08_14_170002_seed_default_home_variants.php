<?php

use App\Models\HomeVariant;
use App\Services\Tenant\TemplateRegistryService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (array_keys((new TemplateRegistryService())->all()) as $slug) {
            HomeVariant::query()->updateOrCreate(
                ['theme_slug' => $slug, 'key' => 'v1'],
                [
                    'name' => 'Default',
                    'description' => 'The original ' . ucfirst($slug) . ' home page layout and colors.',
                    'sections' => null,
                    'colors' => null,
                    'view' => null,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        HomeVariant::query()->where('key', 'v1')->delete();
    }
};
