import 'package:flutter/material.dart';
import '../../features/theme/theme_registry.dart';
import '../shared/sections.dart';

const _mid = [
  'hero',
  'trust_bar',
  'trending_this_week',
  'browse_categories',
  'flash_sale',
  'top_products',
  'new_arrivals',
  'explore_products',
  'recommended_products',
];
const _v5 = [
  'hero',
  'trust_bar',
  'browse_categories',
  'flash_sale',
  'new_arrivals',
  'trending_this_week',
  'explore_products',
  'top_products',
  'recommended_products',
];

/// Rendered section keys per variant (matrix section 2).
const souqifyVariants = <String, List<String>>{
  'v1': [
    'hero',
    'trust_bar',
    'trending_this_week',
    'browse_categories',
    'flash_sale',
    'top_products',
    'hero_banners',
    'new_arrivals',
    'explore_products',
    'featured_products',
    'hot_deals',
    'special_offers',
    'top_rated_products',
    'recommended_products',
    'promo_banner',
  ],
  'v2': _mid,
  'v3': _mid,
  'v4': _mid,
  'v5': _v5,
  'v6': _v5,
};

Widget _pad(Widget w) => Padding(padding: const EdgeInsets.only(bottom: 24), child: w);

final Map<String, SectionBuilder> souqifySectionBuilders = {
  'hero': (c, s) => _pad(HeroBannerCarousel(s)),
  'hero_banners': (c, s) => _pad(HeroBannersGrid(s)),
  'trust_bar': (c, s) => _pad(const TrustBar()),
  'browse_categories': (c, s) => _pad(CategoryCircles(s)),
  'flash_sale': (c, s) => _pad(FlashDealsRail(s)),
  'trending_this_week': (c, s) => _pad(ProductRail(s)),
  'top_products': (c, s) => _pad(ProductRail(s)),
  'new_arrivals': (c, s) => _pad(ProductRail(s)),
  // Web paginates this block with a load-more button.
  'explore_products': (c, s) => _pad(ProductRail(s, grid: true)),
  'featured_products': (c, s) => _pad(ProductRail(s)),
  'hot_deals': (c, s) => _pad(ProductRail(s)),
  'special_offers': (c, s) => _pad(SpecialOffersTeaser(s)),
  'top_rated_products': (c, s) => _pad(ProductRail(s)),
  'recommended_products': (c, s) => _pad(ProductRail(s)),
  'promo_banner': (c, s) => _pad(PromoBanner(s)),
};

ThemeDefinition buildSouqifyDefinition() => ThemeDefinition(
      slug: 'souqify',
      displayName: 'Souqify',
      radius: 10,
      sectionBuilders: souqifySectionBuilders,
    );
