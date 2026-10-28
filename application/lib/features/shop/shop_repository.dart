import '../../core/network/api_client.dart';
import '../../themes/shared/models.dart';
import 'commerce_models.dart';

Map<String, dynamic> asMap(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};
List<Map<String, dynamic>> asList(Object? v) =>
    [for (final e in (v is List ? v : const [])) if (e is Map) asMap(e)];

/// Catalog, search, CMS and preference endpoints (tenant scoped, no login).
abstract class ShopRepository {
  Future<List<CategoryNode>> categories();
  Future<Paginated<ProductItem>> listing(ListingQuery q, int page);
  Future<ProductDetail> product(String slug);
  Future<ReviewPage> reviews(int productId, int page);
  Future<List<ProductItem>> autocomplete(String q);
  Future<CmsPage> cmsPage(String slug);
  Future<List<Map<String, dynamic>>> menuPages();
  Future<PreferenceOptions> preferenceOptions();
}

class RemoteShopRepository implements ShopRepository {
  RemoteShopRepository(this._api);
  final ApiClient _api;

  @override
  Future<List<CategoryNode>> categories() async {
    final d = await _api.request('/categories');
    return [for (final c in asList(d is Map ? d['data'] : d)) CategoryNode.fromJson(c)];
  }

  @override
  Future<Paginated<ProductItem>> listing(ListingQuery q, int page) async {
    final query = {...q.params, 'page': page, 'per_page': 20};
    final String path;
    switch (q.kind) {
      case ListingKind.category:
        path = '/categories/${q.slug}';
      case ListingKind.list:
        path = '/lists/${q.slug}';
      case ListingKind.search:
        path = '/search';
      case ListingKind.offers || ListingKind.all:
        path = '/products';
    }
    final d = asMap(await _api.request(path, query: query));
    return Paginated.products(d['products']);
  }

  @override
  Future<ProductDetail> product(String slug) async {
    final d = asMap(await _api.request('/products/$slug'));
    return ProductDetail.fromJson(asMap(d['product']));
  }

  @override
  Future<ReviewPage> reviews(int productId, int page) async => ReviewPage.fromJson(
      asMap(await _api.request('/products/$productId/reviews', query: {'page': page})));

  @override
  Future<List<ProductItem>> autocomplete(String q) async {
    final d = asMap(await _api.request('/search/autocomplete', query: {'q': q}));
    return productsOf(d['data']);
  }

  @override
  Future<CmsPage> cmsPage(String slug) async {
    final d = asMap(await _api.request('/pages/$slug'));
    return CmsPage('${d['slug']}', d['title'] as String?, d['body'] as String?,
        bodyHtml: d['body_html'] as String?);
  }

  @override
  Future<List<Map<String, dynamic>>> menuPages() async =>
      asList(asMap(await _api.request('/menus'))['pages']);

  @override
  Future<PreferenceOptions> preferenceOptions() async =>
      PreferenceOptions.fromJson(asMap(await _api.request('/preferences/options')));
}
