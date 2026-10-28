import 'package:flutter/material.dart';
import '../../features/theme/theme_registry.dart';
import '../shared/sections.dart';

/// Ecommet (single variant `v1`): sections ported from
/// `resources/views/themes/ecommet/pages/home/sections/*`.
const ecommetVariants = <String, List<String>>{
  'v1': [
    'hero_banner',
    'trust_bar',
    'flash_deals',
    'new_year_sale',
    'flash_sale_strip',
    'best_selling',
    'promo_banner',
  ],
};

Widget _pad(Widget w) => Padding(padding: const EdgeInsets.only(bottom: 20), child: w);

final Map<String, SectionBuilder> ecommetSectionBuilders = {
  'hero_banner': (c, s) => HeroBannerCarousel(s),
  'trust_bar': (c, s) => _pad(Padding(
      padding: const EdgeInsets.only(top: 16), child: const TrustBar())),
  'flash_deals': (c, s) => _pad(FlashDealsRail(s)),
  'new_year_sale': (c, s) => _pad(CategoryPillsProductGrid(s)),
  'flash_sale_strip': (c, s) => _pad(FlashSaleStrip(s)),
  // Web renders tabs + a 2-col grid with "See More".
  'best_selling': (c, s) => _pad(ProductRail(s, grid: true)),
  'promo_banner': (c, s) => _pad(PromoBanner(s)),
};

ThemeDefinition buildEcommetDefinition() => ThemeDefinition(
      slug: 'ecommet',
      displayName: 'Ecommet',
      radius: 8,
      sectionBuilders: ecommetSectionBuilders,
    );
