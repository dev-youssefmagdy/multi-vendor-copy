/// Lightweight parsers for `/home/sections/{key}` payloads (tolerant of gaps).
class ProductItem {
  const ProductItem(
      {required this.id,
      required this.slug,
      required this.name,
      this.image,
      this.price,
      this.basePrice,
      this.isFlash = false,
      this.rating,
      this.reviews});
  final String id, slug, name;
  final String? image;
  final num? price, basePrice;
  final bool isFlash;
  final double? rating;
  final int? reviews;

  bool get hasDiscount =>
      price != null && basePrice != null && basePrice! > price!;
  int get discountPercent =>
      hasDiscount ? (((basePrice! - price!) / basePrice!) * 100).round() : 0;

  factory ProductItem.fromJson(Map<String, dynamic> j) {
    num? n(Object? v) => v is num ? v : num.tryParse('${v ?? ''}');
    final summary = j['reviews_summary'];
    return ProductItem(
      id: '${j['id'] ?? j['slug'] ?? ''}',
      slug: '${j['slug'] ?? j['id'] ?? ''}',
      name: '${j['name'] ?? ''}',
      image: j['image'] as String?,
      price: n(j['price']),
      basePrice: n(j['base_price']),
      isFlash: j['is_flash_sale'] == true,
      rating: n(j['rating'] ?? (summary is Map ? summary['average'] : null))
          ?.toDouble(),
      reviews: n(j['reviews_count'] ?? (summary is Map ? summary['count'] : null))
          ?.toInt(),
    );
  }
}

class CategoryItem {
  const CategoryItem(
      {required this.id, required this.slug, required this.name, this.image});
  final String id, slug, name;
  final String? image;
  factory CategoryItem.fromJson(Map<String, dynamic> j) => CategoryItem(
        id: '${j['id'] ?? j['slug'] ?? ''}',
        slug: '${j['slug'] ?? j['id'] ?? ''}',
        name: '${j['name'] ?? j['slug'] ?? ''}',
        image: j['image'] as String?,
      );
}

List<Map<String, dynamic>> mapList(Object? v) => [
      for (final e in (v is List ? v : const [])) if (e is Map) Map<String, dynamic>.from(e)
    ];

List<ProductItem> productsOf(Object? v) =>
    mapList(v).map(ProductItem.fromJson).toList();
List<CategoryItem> categoriesOf(Object? v) =>
    mapList(v).map(CategoryItem.fromJson).toList();
Map<String, dynamic> mapOf(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};

/// Deep-link style route for a product / category.
String productRoute(ProductItem p) => '/product/${p.slug}';
String categoryRoute(CategoryItem c) => '/category/${c.slug}';
