import 'package:application/features/home/home_repository.dart';
import 'package:application/features/tenant/tenant_models.dart';
import 'fixtures_io.dart';

Map<String, dynamic> product(int i, {bool sale = false}) => {
      'id': i,
      'slug': 'p$i',
      'name': 'Product $i',
      'image': null,
      'price': sale ? 80 : 100,
      'base_price': 100,
      'is_flash_sale': sale,
      'url': 'https://x.test/products/p$i',
    };

List<Map<String, dynamic>> products([int n = 6, bool sale = false]) =>
    [for (var i = 1; i <= n; i++) product(i, sale: sale)];

final categories = [
  for (var i = 1; i <= 6; i++)
    {'id': i, 'slug': 'c$i', 'name': 'Category $i', 'image': null, 'children': []}
];

/// Payloads mirroring `GET /home/sections/{key}` (see HomeController).
Map<String, dynamic> payloadFor(String key) {
  final banners = [
    {'id': 1, 'image': null, 'link': '/categories/c1', 'title': 'Big Sale', 'subtitle': 'Up to 50%', 'button_text': 'Shop'},
    {'id': 2, 'image': null, 'link': null, 'title': null, 'subtitle': null, 'button_text': null},
  ];
  final flash = {
    'sales': [
      {'id': 1, 'discount_percentage': 20, 'ends_at': '2099-01-01T00:00:00Z', 'banner': null}
    ],
    'products': products(5, true),
    'banner': null,
    'coupon_code': 'SAVE10',
    'ends_at': '2099-01-01T00:00:00Z',
  };
  switch (key) {
    case 'hero_banner':
    case 'hero':
    case 'hero_banners':
      return {'banners': banners};
    case 'trust_bar':
      return {};
    case 'category_carousel':
    case 'category_swiper':
    case 'category_strip':
    case 'browse_categories':
      return {'categories': categories};
    case 'new_year_sale':
      return {'categories': categories, 'products': products(10)};
    case 'flash_sale_strip':
    case 'flash_deals':
    case 'flash_sale':
      return {'flash': flash};
    case 'tabbed_products':
      return {
        'tabs': ['cat-flash', 'cat-bestselling', 'cat-newin', 'cat-toprated'],
        'default_tab': 'cat-flash',
      };
    case 'shop_by_category':
      return {'categories': categories, 'products': products(2)};
    case 'promo_banner':
      return {
        'promo': {
          'image': null,
          'mobile_image': null,
          'link': '/categories/c1',
          'title': 'Promo title',
          'subtitle': 'Promo sub',
          'cta_text': null
        }
      };
    default:
      return {'products': products(10), 'see_all': 'x'};
  }
}

class FakeHomeRepository implements HomeRepository {
  final List<String> requested = [];
  @override
  Future<Map<String, dynamic>> section(String key,
      {int? limit, String? tab, bool refresh = false}) async {
    requested.add(key);
    final p = payloadFor(key);
    if (key == 'tabbed_products' && tab != null && tab.isNotEmpty) {
      return {...p, 'tab': tab, 'products': products(4)};
    }
    if (limit != null && p['products'] is List) {
      return {...p, 'products': products(limit)};
    }
    return p;
  }

  @override
  String cacheKey(String key, {int? limit, String? tab}) => key;
  @override
  void clear() {}
}

/// Bootstrap fixture (test/fixtures/bootstrap.json) retargeted to a theme.
TenantConfig bootstrapFor(String theme, String variant, List<String> keys,
    {Map<String, List<String>>? allowed, Map<String, String>? colors}) {
  final j = fx('bootstrap')['data'] as Map<String, dynamic>;
  final t = Map<String, dynamic>.from(j['theme'] as Map)
    ..['slug'] = theme
    ..['variant_key'] = variant;
  if (colors != null) t['colors'] = colors;
  return TenantConfig.fromJson({
    ...j,
    'theme': t,
    'sections': [for (final k in keys) {'key': k, 'widget': 'x', 'source': 'y'}],
    'allowed_section_keys': {
      theme: allowed ?? {variant: keys}
    },
  });
}
