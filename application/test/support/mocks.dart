import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/app.dart';
import 'package:application/app/providers.dart';
import 'package:application/app/router.dart';
import 'package:application/core/errors/app_exception.dart';
import 'package:application/core/platform/media_picker.dart';
import 'package:application/core/platform/web_surface.dart';
import 'package:application/features/account/account_repository.dart';
import 'package:application/features/auth/auth_repository.dart';
import 'package:application/features/cart/cart_repository.dart';
import 'package:application/features/checkout/checkout_repository.dart';
import 'package:application/features/checkout/payment_screens.dart';
import 'package:application/features/home/home_providers.dart';
import 'package:application/features/orders/orders_repository.dart';
import 'package:application/features/shop/commerce_models.dart';
import 'package:application/features/shop/commerce_providers.dart';
import 'package:application/features/shop/shop_repository.dart';
import 'package:application/themes/shared/models.dart';
import 'harness.dart';
import 'home_fixtures.dart';

ProductItem prod(int i, {num price = 10, num? base}) => ProductItem(
    id: '$i', slug: 'p$i', name: 'Product $i', price: price, basePrice: base ?? price);

class FakeShop implements ShopRepository {
  FakeShop({int count = 30}) : all = [for (var i = 1; i <= count; i++) prod(i)];
  final List<ProductItem> all;
  final List<ListingQuery> queries = [];
  final List<int> pages = [];
  Object? failListing;
  List<ProductItem> suggestions = [];
  int perPage = 20;

  @override
  Future<List<CategoryNode>> categories() async => const [
        CategoryNode(id: '1', slug: 'shoes', name: 'Shoes', children: [
          CategoryNode(id: '2', slug: 'boots', name: 'Boots'),
        ]),
      ];

  @override
  Future<Paginated<ProductItem>> listing(ListingQuery q, int page) async {
    queries.add(q);
    pages.add(page);
    if (failListing != null) throw failListing!;
    var src = all;
    if (q.kind == ListingKind.search) {
      src = all.where((p) => p.name.toLowerCase().contains(q.keyword.toLowerCase())).toList();
    }
    final last = (src.length / perPage).ceil().clamp(1, 99);
    final start = (page - 1) * perPage;
    final items = src.skip(start).take(perPage).toList();
    return Paginated(items: items, page: page, lastPage: last, total: src.length);
  }

  @override
  Future<ProductDetail> product(String slug) async {
    final p = all.where((e) => e.slug == slug).firstOrNull;
    if (p == null) throw const ApiException('Product not found.', statusCode: 404);
    return ProductDetail(
        item: p,
        numericId: int.parse(p.id),
        description: '<p>About <b>${p.name}</b></p>',
        variants: const [
          Variant(id: 101, title: 'Red', stock: 5),
          Variant(id: 102, title: 'Blue', stock: 0),
        ],
        ratingAvg: 4.5,
        ratingCount: 2);
  }

  @override
  Future<ReviewPage> reviews(int productId, int page) async => ReviewPage(
      [Review(1, 'Sam', 5, 'Great', null)], 1, 1, 4.5, 1);

  @override
  Future<List<ProductItem>> autocomplete(String q) async => suggestions;

  @override
  Future<CmsPage> cmsPage(String slug) async {
    if (slug == 'missing') throw const ApiException('Page not found.', statusCode: 404);
    return CmsPage(slug, 'About us', '<p>Hello&nbsp;world</p>');
  }

  @override
  Future<List<Map<String, dynamic>>> menuPages() async => [
        {'slug': 'about', 'title': 'About us'}
      ];

  @override
  Future<PreferenceOptions> preferenceOptions() async => const PreferenceOptions(
        locales: [
          {'code': 'en', 'name': 'English'},
          {'code': 'ar', 'name': 'العربية', 'rtl': true},
        ],
        currencies: [
          {'code': 'USD', 'symbol': r'$'},
          {'code': 'EUR', 'symbol': '€'},
        ],
        countries: [
          {'id': 1, 'name': 'Egypt'},
          {'id': 2, 'name': 'France'},
        ],
      );
}

/// Server-cart fake; totals are a fixed server-style computation (10% tax).
class FakeCart implements CartRepository {
  final Map<String, CartLine> lines = {};
  final List<List<Map<String, dynamic>>> merges = [];
  String? coupon;
  Object? failOnce;

  CartData data() {
    final total = lines.values.fold<num>(0, (a, l) => a + l.unitPrice * l.qty);
    return CartData(
        items: lines.values.toList(),
        count: lines.values.fold(0, (a, l) => a + l.qty),
        itemsTotal: total,
        tax: total * 0.1,
        grandTotal: total * 1.1,
        couponCode: coupon,
        couponDiscount: coupon == null ? 0 : 1);
  }

  @override
  Future<CartData> get() async {
    if (failOnce != null) {
      final e = failOnce!;
      failOnce = null;
      throw e;
    }
    return data();
  }

  @override
  Future<CartData> add({required String slug, int? variantId, int qty = 1}) async {
    final key = '$slug:${variantId ?? 0}';
    final old = lines[key];
    lines[key] = CartLine(
        key: key,
        slug: slug,
        name: 'Product ${slug.substring(1)}',
        qty: (old?.qty ?? 0) + qty,
        unitPrice: 10,
        variantId: variantId);
    return data();
  }

  @override
  Future<CartData> update(String key, int qty) async {
    final l = lines[key]!;
    lines[key] = CartLine(
        key: key, slug: l.slug, name: l.name, qty: qty, unitPrice: l.unitPrice, variantId: l.variantId);
    return data();
  }

  @override
  Future<CartData> remove(String key) async {
    lines.remove(key);
    return data();
  }

  @override
  Future<CartData> merge(List<Map<String, dynamic>> items) async {
    merges.add(items);
    for (final i in items) {
      await add(slug: 'p${i['product_id'] ?? i['slug'].toString().substring(1)}',
          variantId: i['variant_id'] as int?, qty: i['qty'] as int);
    }
    return data();
  }

  @override
  Future<void> applyCoupon(String code) async {
    if (code == 'BAD') throw const ApiException('This coupon code is invalid or has expired.', statusCode: 422);
    coupon = code;
  }

  @override
  Future<void> removeCoupon() async => coupon = null;
}

class FakeCheckout implements CheckoutRepository {
  FakeCheckout(this.cart);
  final FakeCart cart;
  final List<Map<String, dynamic>> placedBodies = [];
  final List<String> idemKeys = [];
  final List<Map<String, dynamic>> quoteCalls = [];

  /// When set, the first `place` answers 409 price_changed with this total.
  num? priceChangeTo;
  bool requiresPayment = false;
  Object? placeError;
  int placeCalls = 0;
  String? payUrl = 'https://pay.example.com/hosted';
  bool shippingAvailable = true;

  /// Successive answers for payment-status.
  List<PaymentStatus> statuses = [];
  int statusCalls = 0;

  CartData quoteData([num? total]) {
    final base = cart.data();
    return CartData(
        items: base.items,
        count: base.count,
        itemsTotal: base.itemsTotal,
        tax: base.tax,
        grandTotal: total ?? base.grandTotal + 5,
        shipping: ShippingQuote(shippingAvailable, 5, message: shippingAvailable ? null : 'No shipping to this country'));
  }

  @override
  Future<List<Gateway>> gateways() async => const [
        Gateway(null, 'cod', 'Cash on delivery'),
        Gateway(7, 'stripe', 'Card'),
      ];

  @override
  Future<CartData> quote({int? addressId, int? countryId}) async {
    quoteCalls.add({'address_id': addressId, 'country_id': countryId});
    return quoteData();
  }

  @override
  Future<PlacedOrder> place(Map<String, dynamic> body, {required String idempotencyKey}) async {
    placeCalls++;
    placedBodies.add(body);
    idemKeys.add(idempotencyKey);
    if (placeError != null) throw placeError!;
    if (priceChangeTo != null) {
      final to = priceChangeTo!;
      priceChangeTo = null;
      throw ApiException('Prices have changed.', statusCode: 409, code: 'price_changed', data: {
        'items': [
          for (final l in cart.lines.values)
            {'key': l.key, 'slug': l.slug, 'name': l.name, 'qty': l.qty, 'unit_price': l.unitPrice}
        ],
        'items_total': to - 5,
        'grand_total': to,
        'shipping': {'available': true, 'amount': 5},
      });
    }
    return PlacedOrder(uuid: 'ord-1', requiresPayment: requiresPayment, status: 'pending', grandTotal: body['expected_total'] as num?);
  }

  @override
  Future<String?> pay(String uuid) async => payUrl;

  @override
  Future<PaymentStatus> paymentStatus(String uuid) async {
    final i = statusCalls < statuses.length ? statusCalls : statuses.length - 1;
    statusCalls++;
    return statuses[i];
  }
}

class FakeOrders implements OrdersRepository {
  List<OrderSummary> list = const [
    OrderSummary('ord-1', 'pending', false, 'cod', 33, '2026-01-02T10:00:00Z'),
    OrderSummary('ord-2', 'delivered', true, 'stripe', 12, '2026-01-01T10:00:00Z'),
  ];
  final List<String> cancelled = [];
  final List<Map<String, dynamic>> returnsCreated = [];
  final List<int> photosCounts = [];

  @override
  Future<List<OrderSummary>> orders({String? status}) async => list;

  @override
  Future<OrderDetail> order(String uuid) async => OrderDetail.fromJson({
        'uuid': uuid,
        'status': uuid == 'ord-2' ? 'delivered' : 'pending',
        'paid': uuid == 'ord-2',
        'payment_method': uuid == 'ord-2' ? 'stripe' : 'cod',
        'grand_total': 33,
        'subtotal': 28,
        'shipping_charge': 5,
        'can_cancel': uuid != 'ord-2',
        'items': [
          {'id': 9, 'product_name': 'Product 1', 'qty': 2, 'grand_total': 20}
        ],
      });

  @override
  Future<OrderDetail> cancel(String uuid, {required String reason, String? note}) async {
    cancelled.add('$uuid:$reason');
    return order(uuid);
  }

  @override
  Future<List<Map<String, dynamic>>> cancellationReasons() async => [
        {'value': 'changed_mind', 'label': 'Changed my mind', 'requires_note': false}
      ];

  @override
  Future<int> reorder(String uuid) async => 1;

  @override
  Future<Tracking> tracking(String uuid) async => const Tracking('shipped', true, 'TRK123', [
        TrackingEvent('pending', 'Order placed', null, '2026-01-01T10:00:00Z'),
        TrackingEvent('shipped', 'Shipped', 'On its way', '2026-01-02T10:00:00Z'),
      ]);

  @override
  Future<String> invoiceHtml(String uuid) async => '<html>Invoice $uuid</html>';

  @override
  Future<Map<String, dynamic>> returnOptions() async => {
        'reasons': ['damaged', 'wrong_item'],
        'methods': ['pickup'],
        'types': ['refund', 'exchange'],
      };

  @override
  Future<List<Map<String, dynamic>>> returns() async => [
        {'id': 5, 'order_number': 'ord-2', 'type': 'refund', 'status': 'pending', 'quantity': 1}
      ];

  @override
  Future<Map<String, dynamic>> returnDetail(int id) async => {
        'id': id,
        'order_number': 'ord-2',
        'status': 'pending',
        'reason': 'damaged',
        'quantity': 1,
        'notes': [
          {'note': 'We got it', 'author_type': 'admin', 'created_at': 'x'}
        ],
      };

  @override
  Future<Map<String, dynamic>> createReturn(
      String uuid, Map<String, dynamic> fields, List<ReturnFile> photos) async {
    returnsCreated.add(fields);
    photosCounts.add(photos.length);
    return {'id': 5};
  }
}

class FakeAccount implements AccountRepository {
  Map<String, dynamic> profileData = {
    'id': 1,
    'full_name': 'Sam Buyer',
    'email': 'sam@example.com',
    'phone': '+2010000000'
  };
  List<Map<String, dynamic>> addrs = [
    {
      'id': 3,
      'full_name': 'Sam Buyer',
      'phone': '+2010000000',
      'address_line_1': '1 Nile St',
      'city': 'Cairo',
      'country': 'Egypt',
      'country_id': 1,
      'is_default': true
    },
  ];
  final Set<String> favs = {};
  final List<Map<String, dynamic>> saved = [];

  @override
  Future<Map<String, dynamic>> profile() async => profileData;
  @override
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> f) async {
    profileData = {...profileData, ...f};
    return profileData;
  }

  @override
  Future<void> changePassword(String c, String p, String conf) async {
    if (c != 'old-pass') throw const ApiException('Current password is incorrect.', statusCode: 422);
  }

  @override
  Future<void> deleteAccount(String password) async {}
  @override
  Future<List<Map<String, dynamic>>> addresses() async => addrs;
  @override
  Future<void> saveAddress(Map<String, dynamic> fields, {int? id}) async {
    saved.add(fields);
    if (id == null) addrs = [...addrs, {...fields, 'id': 99}];
  }

  @override
  Future<void> deleteAddress(int id) async => addrs = addrs.where((a) => a['id'] != id).toList();
  @override
  Future<void> setDefaultAddress(int id) async {}
  @override
  Future<List<ProductItem>> favorites() async => [for (final f in favs) prod(int.parse(f))];
  @override
  Future<Set<String>> favoriteIds() async => {...favs};
  @override
  Future<bool> toggleFavorite(int productId) async {
    final id = '$productId';
    return favs.contains(id) ? !favs.remove(id) : favs.add(id);
  }

  @override
  Future<void> submitReview(int productId, int stars, String comment) async {}
}

/// Auth that validates credentials and records calls (token stored like prod).
class ScriptedAuth extends MockAuthRepository {
  ScriptedAuth(super.vault);
  final List<String> calls = [];
  @override
  Future<void> login(String tenantId, String email, String password) async {
    calls.add('login:$email');
    if (password != 'secret123') {
      throw const ApiException('Invalid email or password.', statusCode: 401, code: 'invalid_credentials');
    }
    return super.login(tenantId, email, password);
  }

  @override
  Future<void> register(String tenantId, Map<String, dynamic> fields) async {
    calls.add('register:${fields['email']}');
    if (fields['email'] == 'taken@example.com') {
      throw const ApiException('Validation failed.', statusCode: 422, errors: {
        'email': ['The email has already been taken.']
      });
    }
    return super.register(tenantId, fields);
  }
}

class FakePicker implements MediaPicker {
  @override
  Future<List<PickedFile>> pickPhotos() async => const [PickedFile('/tmp/a.jpg', 'a.jpg')];
}

/// Everything a widget test needs, wired through provider overrides.
class Shop {
  Shop._(this.c, this.shop, this.cart, this.checkout, this.orders, this.account, this.webCalls);
  final ProviderContainer c;
  final FakeShop shop;
  final FakeCart cart;
  final FakeCheckout checkout;
  final FakeOrders orders;
  final FakeAccount account;
  final List<String> webCalls;

  ScriptedAuth get authRepo => c.read(authRepositoryProvider) as ScriptedAuth;

  static Future<Shop> create({FakeShop? shop, bool signedIn = false}) async {
    final cart = FakeCart();
    final webCalls = <String>[];
    final fakeShop = shop ?? FakeShop();
    final checkout = FakeCheckout(cart);
    final orders = FakeOrders();
    final account = FakeAccount();
    final base = await Harness.create();
    final container = ProviderContainer(retry: (_, _) => null, overrides: [
      ...Harness.overrides(base.prefs, base.secure, base.repo, base.links, includeAuth: false),
      authRepositoryProvider.overrideWith((ref) => ScriptedAuth(ref.watch(tokenVaultProvider))),
      shopRepositoryProvider.overrideWithValue(fakeShop),
      cartRepositoryProvider.overrideWithValue(cart),
      checkoutRepositoryProvider.overrideWithValue(checkout),
      ordersRepositoryProvider.overrideWithValue(orders),
      accountRepositoryProvider.overrideWithValue(account),
      mediaPickerProvider.overrideWithValue(FakePicker()),
      homeRepositoryProvider.overrideWithValue(FakeHomeRepository()),
      paymentPollConfigProvider.overrideWithValue(
          const PaymentPollConfig(interval: Duration(milliseconds: 10), maxAttempts: 3)),
      webSurfaceBuilderProvider.overrideWithValue((ctx, {url, html, intercept}) {
        webCalls.add(url ?? 'html:$html');
        return Column(children: [
          Text(url ?? 'html', key: const Key('web_surface')),
          if (intercept != null)
            TextButton(
                key: const Key('web_return'),
                onPressed: () => intercept('storeapp://payment/result?status=success&order=ord-1'),
                child: const Text('return')),
        ]);
      }),
    ]);
    final s = Shop._(container, fakeShop, cart, checkout, orders, account, webCalls);
    if (signedIn) {
      // token present before the store is entered
      await container.read(tokenVaultProvider).save('a1', access: 'tok');
    }
    return s;
  }

  /// Boots the real app, enters shop A and lands on home.
  Future<void> launch(WidgetTester t) async {
    t.view.physicalSize = const Size(420, 1100);
    t.view.devicePixelRatio = 1.0;
    addTearDown(t.view.resetPhysicalSize);
    addTearDown(t.view.resetDevicePixelRatio);
    await t.pumpWidget(UncontrolledProviderScope(container: c, child: const MobileApp()));
    await t.pumpAndSettle();
    await t.enterText(find.byKey(const Key('store_input')), 'shop-a.example.com');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
  }

  Future<void> go(WidgetTester t, String location) async {
    c.read(routerProvider).go(location);
    await t.pumpAndSettle();
  }

  Future<void> push(WidgetTester t, String location) async {
    c.read(routerProvider).push(location);
    await t.pumpAndSettle();
  }
}
