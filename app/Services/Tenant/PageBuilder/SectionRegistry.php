<?php

namespace App\Services\Tenant\PageBuilder;

/**
 * Canonical, source-order definition of each theme's page sections.
 *
 * Used both as the fallback order/visibility for tenants who have not
 * customized a page yet, and as the list of valid section_keys shown in the
 * Page Builder admin screen. Section partials live at
 * themes/{theme}/pages/{page}/sections/{section_key}.blade.php.
 */
class SectionRegistry
{
    private const SECTIONS = [
        'ecommet' => [
            'home' => [
                'hero_banner' => 'Hero Banner',
                'trust_bar' => 'Trust Bar',
                'flash_deals' => 'Flash / Lightning Deals',
                'new_year_sale' => 'Category Pills + Product Grid',
                'flash_sale_strip' => 'Flash Sale Banner Strip',
                'best_selling' => 'Best-Selling Items',
                'promo_banner' => 'Promotional Banner',
            ],
        ],
        'elora' => [
            'home' => [
                'hero_banner' => 'Hero Banner',
                'trust_bar' => 'Trust Bar',
                'category_circles' => 'Category Circles',
                'tabbed_products' => 'Tabbed Products',
                'flash_sale_strip' => 'Flash Sale Strip',
                'trending_now' => 'Trending Now',
                'category_strip' => 'Orange Category Strip',
                'best_seller' => 'Best Seller',
                'new_arrivals' => 'New Arrivals',
                'shop_by_category' => 'Shop by Category',
                'recommended_for_you' => 'Recommended For You',
                'promo_banner' => 'Promotional Banner',
            ],
        ],
        'souqify' => [
            'home' => [
                'hero' => 'Hero',
                'trust_bar' => 'Trust Bar',
                'trending_this_week' => 'Trending This Week',
                'browse_categories' => 'Browse by Categories',
                'flash_sale' => "Today's Flash Sale",
                'top_products' => 'Top Products',
                'hero_banners' => 'Hero Banners',
                'new_arrivals' => 'New Arrivals',
                'explore_products' => 'Explore Products',
                'featured_products' => 'Featured Products',
                'hot_deals' => 'Hot Deals',
                'special_offers' => 'Special Offers',
                'top_rated_products' => 'Top Rated Products',
                'recommended_products' => 'Recommended Products',
                'promo_banner' => 'Promotional Banner',
            ],
        ],
        'nexo' => [
            'home' => [
                'hero_banner' => 'Hero Banner',
                'trust_bar' => 'Trust Bar',
                'featured_products' => 'Featured Products',
                'categories' => 'Shop by Category',
                'flash_sale' => 'Flash Sale',
                'new_arrivals' => 'New Arrivals',
                'best_sellers' => 'Best Sellers',
                'promo_banner' => 'Promotional Banner',
            ],
        ],
    ];

    /** @return string[] section keys in default order */
    public static function defaultsFor(string $theme, string $page): array
    {
        return array_keys(self::SECTIONS[$theme][$page] ?? []);
    }

    /** @return array<string,string> section_key => label */
    public static function labelsFor(string $theme, string $page): array
    {
        return self::SECTIONS[$theme][$page] ?? [];
    }
}
