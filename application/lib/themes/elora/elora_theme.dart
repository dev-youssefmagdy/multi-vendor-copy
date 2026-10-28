import 'package:flutter/material.dart';
import '../../features/theme/theme_registry.dart';
import '../shared/skin.dart';
import '../shared/sections.dart';

const _v2 = [
  'hero_banner',
  'trust_bar',
  'category_carousel',
  'tabbed_products',
  'flash_sale_strip',
  'trending_now',
  'category_strip',
  'best_seller',
  'new_arrivals',
  'shop_by_category',
  'recommended_for_you',
  'promo_banner',
];
const _v4 = [
  'hero_banner',
  'trust_bar',
  'tabbed_products',
  'flash_sale_strip',
  'trending_now',
  'category_strip',
  'best_seller',
  'new_arrivals',
  'shop_by_category',
  'recommended_for_you',
  'promo_banner',
];

/// Rendered section keys per variant (matrix section 2).
const eloraVariants = <String, List<String>>{
  'v1': [
    'hero_banner',
    'trust_bar',
    'tabbed_products',
    'category_swiper',
    'flash_sale_strip',
    'trending_now',
    'category_strip',
    'best_seller',
    'new_arrivals',
    'shop_by_category',
    'recommended_for_you',
    'promo_banner',
  ],
  'v2': _v2,
  'v3': _v2,
  'v4': _v4,
  'v5': _v4,
  'v6': _v2,
};

Widget _pad(Widget w) => Padding(padding: const EdgeInsets.only(bottom: 24), child: w);

/// Variants whose best-seller block is a wide-card grid (v3/v5 partials
/// `best_seller_card` / `wide_card`) instead of a rail.
bool _gridBest(BuildContext c) {
  final v = SkinScope.of(c).variantKey;
  return v == 'v3' || v == 'v5';
}

final Map<String, SectionBuilder> eloraSectionBuilders = {
  'hero_banner': (c, s) => _pad(HeroBannerCarousel(s)),
  'trust_bar': (c, s) => _pad(const TrustBar()),
  'category_carousel': (c, s) => _pad(CategoryCircles(s)),
  'category_swiper': (c, s) => _pad(CategoryCircles(s, rows: 2)),
  'category_strip': (c, s) => _pad(CategoryCircles(s, maxItems: 12)),
  'tabbed_products': (c, s) => _pad(TabbedProducts(s)),
  'flash_sale_strip': (c, s) => _pad(FlashSaleStrip(s)),
  'trending_now': (c, s) => _pad(ProductRail(s)),
  'best_seller': (c, s) => _pad(ProductRail(s, grid: _gridBest(c))),
  'new_arrivals': (c, s) => _pad(ProductRail(s)),
  'shop_by_category': (c, s) => _pad(ShopByCategoryBuyTogether(s)),
  'recommended_for_you': (c, s) => _pad(ProductRail(s)),
  'promo_banner': (c, s) => _pad(PromoBanner(s)),
};

ThemeDefinition buildEloraDefinition() => ThemeDefinition(
      slug: 'elora',
      displayName: 'Elora',
      radius: 14,
      sectionBuilders: eloraSectionBuilders,
    );
