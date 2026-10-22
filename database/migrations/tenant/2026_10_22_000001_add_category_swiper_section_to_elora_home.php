<?php

use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers the Elora "category_swiper" home section for tenants that already
 * saved a custom Page Builder order, placing it right after "tabbed_products".
 * Tenants without saved rows pick it up from SectionRegistry's default order.
 */
return new class extends Migration
{
    private const KEY = 'category_swiper';
    private const AFTER = 'tabbed_products';

    public function up(): void
    {
        $theme = Theme::query()->where('slug', 'elora')->first();
        if (!$theme) {
            return;
        }

        $anchors = TenantPageSection::query()
            ->where('theme_id', $theme->id)
            ->where('page', 'home')
            ->where('section_key', self::AFTER)
            ->get();

        foreach ($anchors as $anchor) {
            $scope = fn () => TenantPageSection::query()
                ->where('theme_id', $theme->id)
                ->where('home_variant_id', $anchor->home_variant_id)
                ->where('page', 'home');

            if ($scope()->where('section_key', self::KEY)->exists()) {
                continue;
            }

            $scope()->where('sort_order', '>', $anchor->sort_order)->increment('sort_order');

            TenantPageSection::query()->create([
                'theme_id' => $theme->id,
                'home_variant_id' => $anchor->home_variant_id,
                'page' => 'home',
                'section_key' => self::KEY,
                'sort_order' => $anchor->sort_order + 1,
                'is_visible' => true,
            ]);
        }
    }

    public function down(): void
    {
        $theme = Theme::query()->where('slug', 'elora')->first();
        if ($theme) {
            TenantPageSection::query()
                ->where('theme_id', $theme->id)
                ->where('page', 'home')
                ->where('section_key', self::KEY)
                ->delete();
        }
    }
};
