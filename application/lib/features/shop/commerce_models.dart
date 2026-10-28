import '../../themes/shared/models.dart';

num? _n(Object? v) => v is num ? v : num.tryParse('${v ?? ''}');
Map<String, dynamic> _m(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};
List<Map<String, dynamic>> _l(Object? v) =>
    [for (final e in (v is List ? v : const [])) if (e is Map) _m(e)];

class Paginated<T> {
  const Paginated(
      {required this.items,
      required this.page,
      required this.lastPage,
      this.total = 0});
  final List<T> items;
  final int page, lastPage, total;
  bool get hasMore => page < lastPage;

  static Paginated<ProductItem> products(Object? json) {
    final m = _m(json);
    final meta = _m(m['meta']);
    return Paginated(
        items: productsOf(m['data']),
        page: _n(meta['current_page'])?.toInt() ?? 1,
        lastPage: _n(meta['last_page'])?.toInt() ?? 1,
        total: _n(meta['total'])?.toInt() ?? 0);
  }
}

enum ListingKind { all, category, list, offers, search }

/// Identity of a product listing (value equality => provider family key).
class ListingQuery {
  const ListingQuery(this.kind,
      {this.slug = '',
      this.keyword = '',
      this.sort = 'latest',
      this.min,
      this.max,
      this.availability = '',
      this.ratings});
  final ListingKind kind;
  final String slug, keyword, sort, availability;
  final num? min, max;
  final int? ratings;

  ListingQuery copyWith(
          {String? sort,
          num? min,
          num? max,
          String? availability,
          int? ratings,
          bool clearPrice = false,
          bool clearRatings = false}) =>
      ListingQuery(kind,
          slug: slug,
          keyword: keyword,
          sort: sort ?? this.sort,
          min: clearPrice ? null : (min ?? this.min),
          max: clearPrice ? null : (max ?? this.max),
          availability: availability ?? this.availability,
          ratings: clearRatings ? null : (ratings ?? this.ratings));

  bool get hasFilters =>
      min != null || max != null || availability.isNotEmpty || ratings != null;

  Map<String, dynamic> get params => {
        if (keyword.isNotEmpty) 'keyword': keyword,
        if (sort != 'latest') 'sort': sort,
        'min': ?min,
        'max': ?max,
        if (availability.isNotEmpty) 'availability': availability,
        'ratings': ?ratings,
        if (kind == ListingKind.offers) 'sale': 'on_sale',
      };

  @override
  bool operator ==(Object other) =>
      other is ListingQuery &&
      other.kind == kind &&
      other.slug == slug &&
      other.keyword == keyword &&
      other.sort == sort &&
      other.min == min &&
      other.max == max &&
      other.availability == availability &&
      other.ratings == ratings;
  @override
  int get hashCode =>
      Object.hash(kind, slug, keyword, sort, min, max, availability, ratings);
}

class CategoryNode {
  const CategoryNode(
      {required this.id,
      required this.slug,
      required this.name,
      this.image,
      this.children = const []});
  final String id, slug, name;
  final String? image;
  final List<CategoryNode> children;
  factory CategoryNode.fromJson(Map<String, dynamic> j) => CategoryNode(
        id: '${j['id'] ?? j['slug']}',
        slug: '${j['slug'] ?? j['id']}',
        name: '${j['name'] ?? j['slug'] ?? ''}',
        image: j['image'] as String?,
        children: [for (final c in _l(j['children'])) CategoryNode.fromJson(c)],
      );
}

class Variant {
  const Variant(
      {required this.id,
      required this.title,
      this.sku,
      this.active = true,
      this.stock});
  final int id;
  final String title;
  final String? sku;
  final bool active;
  final int? stock;
  bool get available => active && (stock == null || stock! > 0);
  factory Variant.fromJson(Map<String, dynamic> j) => Variant(
        id: _n(j['id'])?.toInt() ?? 0,
        title: '${j['title'] ?? j['sku'] ?? ''}',
        sku: j['sku'] as String?,
        active: j['active'] != false,
        stock: _n(j['stock'])?.toInt(),
      );
}

class ProductDetail {
  const ProductDetail(
      {required this.item,
      required this.numericId,
      this.description,
      this.variants = const [],
      this.ratingAvg = 0,
      this.ratingCount = 0});
  final ProductItem item;
  final int numericId;
  final String? description;
  final List<Variant> variants;
  final double ratingAvg;
  final int ratingCount;

  factory ProductDetail.fromJson(Map<String, dynamic> j) {
    final rs = _m(j['reviews_summary']);
    return ProductDetail(
      item: ProductItem.fromJson(j),
      numericId: _n(j['id'])?.toInt() ?? 0,
      description: j['description'] as String?,
      variants: [for (final v in _l(j['variants'])) Variant.fromJson(v)],
      ratingAvg: _n(rs['average'])?.toDouble() ?? 0,
      ratingCount: _n(rs['count'])?.toInt() ?? 0,
    );
  }
}

class Review {
  const Review(this.id, this.author, this.stars, this.comment, this.createdAt);
  final int id, stars;
  final String? author, comment, createdAt;
  factory Review.fromJson(Map<String, dynamic> j) => Review(
      _n(j['id'])?.toInt() ?? 0,
      j['customer_name'] as String?,
      _n(j['stars'])?.toInt() ?? 0,
      j['comment'] as String?,
      j['created_at'] as String?);
}

class ReviewPage {
  const ReviewPage(this.reviews, this.page, this.lastPage, this.average, this.count);
  final List<Review> reviews;
  final int page, lastPage, count;
  final double average;
  factory ReviewPage.fromJson(Map<String, dynamic> j) {
    final meta = _m(j['meta']);
    final sum = _m(j['summary']);
    return ReviewPage(
        [for (final r in _l(j['data'])) Review.fromJson(r)],
        _n(meta['current_page'])?.toInt() ?? 1,
        _n(meta['last_page'])?.toInt() ?? 1,
        _n(sum['average'])?.toDouble() ?? 0,
        _n(sum['count'])?.toInt() ?? 0);
  }
}

class CmsPage {
  const CmsPage(this.slug, this.title, this.body);
  final String slug;
  final String? title, body;
}

class PreferenceOptions {
  const PreferenceOptions(
      {this.locales = const [], this.currencies = const [], this.countries = const []});
  final List<Map<String, dynamic>> locales, currencies, countries;
  factory PreferenceOptions.fromJson(Map<String, dynamic> j) => PreferenceOptions(
      locales: _l(j['locales']),
      currencies: _l(j['currencies']),
      countries: _l(j['countries']));
}

// ---------------------------------------------------------------- cart ----

class CartLine {
  const CartLine(
      {required this.key,
      required this.slug,
      required this.name,
      required this.qty,
      required this.unitPrice,
      this.variantId,
      this.productId,
      this.image,
      this.originalPrice,
      this.lineTotal,
      this.outOfStock = false,
      this.isFlash = false});
  final String key, slug, name;
  final int qty;
  final num unitPrice;
  final int? variantId, productId;
  final String? image;
  final num? originalPrice, lineTotal;
  final bool outOfStock, isFlash;

  num get total => lineTotal ?? unitPrice * qty;

  factory CartLine.fromJson(Map<String, dynamic> j) => CartLine(
        key: '${j['key']}',
        slug: '${j['slug'] ?? ''}',
        name: '${j['name'] ?? ''}',
        qty: _n(j['qty'])?.toInt() ?? 1,
        unitPrice: _n(j['unit_price']) ?? 0,
        variantId: _n(j['variant_id'])?.toInt(),
        productId: _n(j['product_id'])?.toInt(),
        image: j['image'] as String?,
        originalPrice: _n(j['original_price']),
        lineTotal: _n(j['line_total']),
        outOfStock: j['out_of_stock'] == true,
        isFlash: j['is_flash_sale'] == true,
      );
}

class ShippingQuote {
  const ShippingQuote(this.available, this.amount, {this.zone, this.rate, this.message});
  final bool available;
  final num amount;
  final String? zone, rate, message;
}

/// Server cart / checkout quote (`MobileCartPresenter::build`). All amounts
/// come from the server; the client never recomputes totals.
class CartData {
  const CartData(
      {this.items = const [],
      this.count = 0,
      this.itemsTotal = 0,
      this.tax = 0,
      this.grandTotal = 0,
      this.couponCode,
      this.couponDiscount = 0,
      this.couponValid = true,
      this.couponError,
      this.shipping,
      this.freeShippingPct,
      this.hasUnavailable = false,
      this.currencySymbol,
      this.isGuest = false});
  final List<CartLine> items;
  final int count;
  final num itemsTotal, tax, grandTotal, couponDiscount;
  final String? couponCode, couponError, currencySymbol;
  final bool couponValid, hasUnavailable, isGuest;
  final ShippingQuote? shipping;
  final int? freeShippingPct;

  bool get isEmpty => items.isEmpty;

  factory CartData.fromJson(Map<String, dynamic> j) {
    final c = j['coupon'] is Map ? _m(j['coupon']) : null;
    final s = j['shipping'] is Map ? _m(j['shipping']) : null;
    final fs = _m(j['free_shipping']);
    final items = [for (final i in _l(j['items'])) CartLine.fromJson(i)];
    return CartData(
      items: items,
      count: _n(j['count'])?.toInt() ?? items.fold(0, (a, b) => a + b.qty),
      itemsTotal: _n(j['items_total']) ?? 0,
      tax: _n(j['tax']) ?? 0,
      grandTotal: _n(j['grand_total']) ?? 0,
      couponCode: c?['code'] as String?,
      couponDiscount: _n(c?['discount']) ?? 0,
      couponValid: c == null || c['valid'] != false,
      couponError: c?['error'] as String?,
      shipping: s == null
          ? null
          : ShippingQuote(s['available'] == true, _n(s['amount']) ?? 0,
              zone: s['zone'] as String?,
              rate: s['rate'] as String?,
              message: s['message'] as String?),
      freeShippingPct: _n(fs['progress_pct'])?.toInt(),
      hasUnavailable: j['has_unavailable'] == true,
      currencySymbol: _m(j['currency'])['symbol'] as String?,
    );
  }
}

class Gateway {
  const Gateway(this.id, this.code, this.name, {this.logo});
  final int? id;
  final String code, name;
  final String? logo;
  bool get isCod => code == 'cod';
  factory Gateway.fromJson(Map<String, dynamic> j) => Gateway(
      _n(j['id'])?.toInt(), '${j['code']}', '${j['name'] ?? j['code']}',
      logo: j['logo_url'] as String?);
}

class PlacedOrder {
  const PlacedOrder(
      {required this.uuid,
      required this.requiresPayment,
      this.status,
      this.grandTotal,
      this.stockWarnings = const []});
  final String uuid;
  final bool requiresPayment;
  final String? status;
  final num? grandTotal;
  final List<dynamic> stockWarnings;
  factory PlacedOrder.fromJson(Map<String, dynamic> j) {
    final o = _m(j['order']);
    return PlacedOrder(
        uuid: '${o['uuid']}',
        requiresPayment: j['requires_payment'] == true,
        status: o['status'] as String?,
        grandTotal: _n(o['grand_total']),
        stockWarnings: (j['stock_warnings'] as List?) ?? const []);
  }
}

class PaymentStatus {
  const PaymentStatus(this.uuid, this.paid, this.status);
  final String uuid;
  final bool paid;
  final String? status;
  bool get closed => const {'cancelled', 'canceled', 'failed', 'refunded'}.contains(status);
}

// -------------------------------------------------------------- orders ----

class OrderSummary {
  const OrderSummary(this.uuid, this.status, this.paid, this.paymentMethod,
      this.grandTotal, this.createdAt);
  final String uuid;
  final String? status, paymentMethod, createdAt;
  final bool paid;
  final num? grandTotal;
  factory OrderSummary.fromJson(Map<String, dynamic> j) => OrderSummary(
      '${j['uuid']}',
      j['status'] as String?,
      j['paid'] == true,
      j['payment_method'] as String?,
      _n(j['grand_total']),
      j['created_at'] as String?);
}

class OrderDetail {
  const OrderDetail(this.summary, this.raw);
  final OrderSummary summary;
  final Map<String, dynamic> raw;
  bool get canCancel => raw['can_cancel'] == true;
  bool get suggestReturn => raw['can_request_return_instead'] == true;
  String? get cancelBlockedReason => raw['cancel_blocked_reason'] as String?;
  List<Map<String, dynamic>> get items => _l(raw['items']);
  num? num_(String k) => _n(raw[k]);
  factory OrderDetail.fromJson(Map<String, dynamic> j) =>
      OrderDetail(OrderSummary.fromJson(j), j);
}

class TrackingEvent {
  const TrackingEvent(this.status, this.title, this.description, this.at);
  final String? status, title, description, at;
}

class Tracking {
  const Tracking(this.status, this.paid, this.trackingNumber, this.timeline);
  final String? status, trackingNumber;
  final bool paid;
  final List<TrackingEvent> timeline;
  factory Tracking.fromJson(Map<String, dynamic> j) => Tracking(
      j['status'] as String?,
      j['paid'] == true,
      j['tracking_number'] as String?,
      [
        for (final e in _l(j['timeline']))
          TrackingEvent(e['status']?.toString(), e['title'] as String?,
              e['description'] as String?, e['at'] as String?)
      ]);
}
