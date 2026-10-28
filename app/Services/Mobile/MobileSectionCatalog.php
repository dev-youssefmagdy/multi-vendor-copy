<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\HomeVariant;
use App\Services\Tenant\PageBuilder\SectionRegistry;

/**
 * Section-key knowledge for the mobile app: Flutter widget + data source per key,
 * and the per-(theme, variant) allowed-key map.
 *
 * SectionRegistry is one list per theme, but each variant folder only ships some
 * partials (a key without a partial renders nothing on the web). The map below is
 * derived from the files actually present, so the mobile app never receives keys
 * that the chosen variant cannot render (e.g. elora v1/v4/v5 `category_carousel`,
 * souqify v2..v6 `hero_banners`, `featured_products`, ...).
 */
class MobileSectionCatalog
{
    public const THEMES = ['ecommet', 'elora', 'souqify'];

    /** key => [widget, source]. `source` selects the data builder in HomeSectionData. */
    public const META = [
        'hero_banner' => ['HeroBannerCarousel', 'banners'],
        'hero' => ['HeroBannerCarousel', 'banners'],
        'hero_banners' => ['HeroBannersGrid', 'banners'],
        'trust_bar' => ['TrustBar', 'static'],
        'category_carousel' => ['CategoryCircles', 'categories'],
        'category_swiper' => ['CategoryCircles', 'categories'],
        'category_strip' => ['CategoryCircles', 'categories'],
        'browse_categories' => ['CategoryCircles', 'categories'],
        'new_year_sale' => ['CategoryPillsProductGrid', 'categories_new_in'],
        'flash_sale_strip' => ['FlashSaleStrip', 'flash'],
        'flash_deals' => ['FlashDealsRail', 'flash'],
        'flash_sale' => ['FlashDealsRail', 'flash'],
        'tabbed_products' => ['TabbedProducts', 'tabbed'],
        'shop_by_category' => ['ShopByCategoryBuyTogether', 'buy_together'],
        'promo_banner' => ['PromoBanner', 'promo'],
        'special_offers' => ['SpecialOffersTeaser', 'products'],
        'trending_now' => ['ProductRail', 'trending'],
        'trending_this_week' => ['ProductRail', 'trending'],
        'best_seller' => ['ProductRail', 'best_selling'],
        'best_selling' => ['ProductRail', 'best_selling'],
        'new_arrivals' => ['ProductRail', 'new_in'],
        'recommended_for_you' => ['ProductRail', 'recommended'],
        'recommended_products' => ['ProductRail', 'recommended'],
        'top_products' => ['ProductRail', 'top_rated'],
        'top_rated_products' => ['ProductRail', 'top_rated'],
        'explore_products' => ['ProductRail', 'products'],
        'featured_products' => ['ProductRail', 'featured'],
        'hot_deals' => ['ProductRail', 'hot_deals'],
    ];

    public static function widgetFor(string $key): ?string
    {
        return self::META[$key][0] ?? null;
    }

    public static function sourceFor(string $key): ?string
    {
        return self::META[$key][1] ?? null;
    }

    /** blade page folder of a variant: "home" for the base layout, "home-vN" otherwise. */
    public static function folderFor(string $themeSlug, string $variantKey, ?string $view = null): string
    {
        if ($view && preg_match('/\.(home(?:-v\d+)?)$/', $view, $m)) {
            return $m[1];
        }

        return $variantKey === 'v1' ? 'home' : 'home-'.$variantKey;
    }

    /**
     * Registry keys of the theme that have a partial in this variant's folder.
     *
     * @return string[]
     */
    public static function allowedKeys(string $themeSlug, string $variantKey, ?string $view = null): array
    {
        $folder = self::folderFor($themeSlug, $variantKey, $view);

        return array_values(array_filter(
            SectionRegistry::defaultsFor($themeSlug, 'home'),
            fn (string $key) => isset(self::META[$key]) && SectionRegistry::sectionViewExists($themeSlug, $folder, $key),
        ));
    }

    /** @return array<string, array<string, string[]>> theme => variantKey => allowed keys */
    public static function allowedMap(): array
    {
        $map = [];
        $variants = HomeVariant::query()->whereIn('theme_slug', self::THEMES)->where('is_active', true)->orderBy('id')->get();

        foreach ($variants as $variant) {
            $map[$variant->theme_slug][$variant->key] = self::allowedKeys($variant->theme_slug, $variant->key, $variant->view);
        }

        return $map;
    }
}
