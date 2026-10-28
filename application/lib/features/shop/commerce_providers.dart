import 'dart:convert';
import 'dart:math';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../../core/errors/app_exception.dart';
import '../../core/storage/key_value_store.dart';
import '../../themes/shared/models.dart';
import '../account/account_repository.dart';
import '../cart/cart_repository.dart';
import '../checkout/checkout_repository.dart';
import '../orders/orders_repository.dart';
import 'commerce_models.dart';
import 'shop_repository.dart';

/// Every tenant-scoped provider calls this first: when the store changes the
/// epoch / tenant id change and the provider is rebuilt from scratch, so no
/// cart, quote, favorites or order data survives a tenant switch.
String? watchScope(Ref ref) {
  ref.watch(sessionEpochProvider);
  return ref.watch(tenantControllerProvider.select((s) => s.tenant?.id));
}

// ------------------------------------------------------- repositories ----

final shopRepositoryProvider = Provider<ShopRepository>((ref) {
  watchScope(ref);
  return RemoteShopRepository(ref.watch(apiClientProvider));
});
final cartRepositoryProvider = Provider<CartRepository>((ref) {
  watchScope(ref);
  return RemoteCartRepository(ref.watch(apiClientProvider));
});
final checkoutRepositoryProvider = Provider<CheckoutRepository>((ref) {
  watchScope(ref);
  return RemoteCheckoutRepository(ref.watch(apiClientProvider));
});
final ordersRepositoryProvider = Provider<OrdersRepository>((ref) {
  watchScope(ref);
  return RemoteOrdersRepository(ref.watch(apiClientProvider));
});
final accountRepositoryProvider = Provider<AccountRepository>((ref) {
  watchScope(ref);
  return RemoteAccountRepository(ref.watch(apiClientProvider));
});

// ---------------------------------------------------------- preferences ----

class PrefsState {
  const PrefsState({this.locale = 'en', this.currency, this.countryId});
  final String locale;
  final String? currency;
  final int? countryId;
}

/// Language / currency / country. Persisted per tenant, mirrored into the
/// [ApiSession] so every request carries Accept-Language / X-Currency /
/// X-Country.
class PreferencesController extends Notifier<PrefsState> {
  @override
  PrefsState build() {
    final id = watchScope(ref);
    final session = ref.read(apiSessionProvider);
    final cfg = ref.read(tenantControllerProvider).config;
    final prefs = ref.read(sharedPreferencesProvider);
    String? get(String k) => id == null ? null : prefs.getString(tenantKey(id, k));
    final defLocale = cfg?.locales.where((l) => l.isDefault).firstOrNull?.code ??
        cfg?.locales.firstOrNull?.code ??
        'en';
    final defCur = cfg?.currencies.where((c) => c.isDefault).firstOrNull?.code;
    final s = PrefsState(
        locale: get('pref_locale') ?? defLocale,
        currency: get('pref_currency') ?? defCur,
        countryId: int.tryParse(get('pref_country') ?? ''));
    session
      ..locale = s.locale
      ..currency = s.currency
      ..countryId = s.countryId;
    return s;
  }

  Future<void> set({String? locale, String? currency, int? countryId}) async {
    final id = ref.read(tenantControllerProvider).tenant?.id;
    final prefs = ref.read(sharedPreferencesProvider);
    final s = PrefsState(
        locale: locale ?? state.locale,
        currency: currency ?? state.currency,
        countryId: countryId ?? state.countryId);
    if (id != null) {
      await prefs.setString(tenantKey(id, 'pref_locale'), s.locale);
      if (s.currency != null) await prefs.setString(tenantKey(id, 'pref_currency'), s.currency!);
      if (s.countryId != null) await prefs.setString(tenantKey(id, 'pref_country'), '${s.countryId}');
    }
    ref.read(apiSessionProvider)
      ..locale = s.locale
      ..currency = s.currency
      ..countryId = s.countryId;
    state = s;
  }
}

final prefsProvider =
    NotifierProvider<PreferencesController, PrefsState>(PreferencesController.new);

final preferenceOptionsProvider = FutureProvider<PreferenceOptions>((ref) {
  watchScope(ref);
  return ref.watch(shopRepositoryProvider).preferenceOptions();
});

/// Currency symbol of the selected currency (bootstrap currencies list).
final currencySymbolProvider = Provider<String>((ref) {
  final cfg = ref.watch(tenantControllerProvider.select((s) => s.config));
  final code = ref.watch(prefsProvider.select((p) => p.currency));
  final cur = cfg?.currencies.where((c) => c.code == code).firstOrNull ??
      cfg?.currencies.where((c) => c.isDefault).firstOrNull;
  return cur?.symbol ?? cur?.code ?? '';
});

// -------------------------------------------------------------- catalog ----

final categoriesProvider = FutureProvider<List<CategoryNode>>((ref) {
  watchScope(ref);
  ref.watch(prefsProvider);
  return ref.watch(shopRepositoryProvider).categories();
});

final productProvider =
    FutureProvider.family<ProductDetail, String>((ref, slug) {
  watchScope(ref);
  ref.watch(prefsProvider);
  return ref.watch(shopRepositoryProvider).product(slug);
});

final cmsPageProvider = FutureProvider.family<CmsPage, String>((ref, slug) {
  watchScope(ref);
  ref.watch(prefsProvider);
  return ref.watch(shopRepositoryProvider).cmsPage(slug);
});

final menuPagesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) {
  watchScope(ref);
  return ref.watch(shopRepositoryProvider).menuPages();
});

class PagedState<T> {
  const PagedState(
      {this.items = const [],
      this.page = 0,
      this.lastPage = 1,
      this.loading = true,
      this.error,
      this.total = 0});
  final List<T> items;
  final int page, lastPage, total;
  final bool loading;
  final Object? error;
  bool get hasMore => page < lastPage;
  bool get initialLoading => loading && items.isEmpty;
  PagedState<T> copyWith(
          {List<T>? items,
          int? page,
          int? lastPage,
          bool? loading,
          Object? error,
          bool clearError = false,
          int? total}) =>
      PagedState(
          items: items ?? this.items,
          page: page ?? this.page,
          lastPage: lastPage ?? this.lastPage,
          loading: loading ?? this.loading,
          error: clearError ? null : (error ?? this.error),
          total: total ?? this.total);
}

/// Infinite-scroll listing for one [ListingQuery].
class ListingNotifier extends Notifier<PagedState<ProductItem>> {
  ListingNotifier(this.query);
  final ListingQuery query;

  @override
  PagedState<ProductItem> build() {
    watchScope(ref);
    ref.watch(prefsProvider);
    Future.microtask(() => _load(1));
    return const PagedState();
  }

  Future<void> _load(int page) async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final r = await ref.read(shopRepositoryProvider).listing(query, page);
      state = state.copyWith(
          items: page == 1 ? r.items : [...state.items, ...r.items],
          page: r.page,
          lastPage: r.lastPage,
          total: r.total,
          loading: false);
    } on AppException catch (e) {
      state = state.copyWith(loading: false, error: e);
    }
  }

  Future<void> refresh() => _load(1);
  Future<void> loadMore() async {
    if (state.loading || !state.hasMore) return;
    await _load(state.page + 1);
  }

  Future<void> retry() => state.items.isEmpty ? _load(1) : _load(state.page + 1);
}

final listingProvider = NotifierProvider.autoDispose
    .family<ListingNotifier, PagedState<ProductItem>, ListingQuery>(
        ListingNotifier.new);

// ----------------------------------------------------------------- cart ----

/// Guest (not signed in) cart kept on the device, per tenant. Prices shown for
/// guest lines are only snapshots; checkout needs a login and then uses the
/// server cart (prices are always recomputed server-side).
class GuestCartStore {
  GuestCartStore(this._ref);
  final Ref _ref;

  String? get _key {
    final id = _ref.read(tenantControllerProvider).tenant?.id;
    return id == null ? null : tenantKey(id, 'guest_cart');
  }

  List<Map<String, dynamic>> read() {
    final k = _key;
    if (k == null) return [];
    final raw = _ref.read(sharedPreferencesProvider).getString(k);
    if (raw == null) return [];
    try {
      return [for (final e in jsonDecode(raw) as List) Map<String, dynamic>.from(e as Map)];
    } catch (_) {
      return [];
    }
  }

  Future<void> write(List<Map<String, dynamic>> items) async {
    final k = _key;
    if (k == null) return;
    await _ref.read(sharedPreferencesProvider).setString(k, jsonEncode(items));
  }

  CartData toCart(List<Map<String, dynamic>> items) {
    num total = 0;
    final lines = <CartLine>[];
    for (final i in items) {
      final qty = (i['qty'] as num?)?.toInt() ?? 1;
      final price = (i['price'] as num?) ?? 0;
      total += price * qty;
      lines.add(CartLine(
          key: '${i['slug']}:${i['variant_id'] ?? 0}',
          slug: '${i['slug']}',
          name: '${i['name'] ?? i['slug']}',
          qty: qty,
          unitPrice: price,
          variantId: (i['variant_id'] as num?)?.toInt(),
          image: i['image'] as String?));
    }
    return CartData(
        items: lines,
        count: lines.fold(0, (a, b) => a + b.qty),
        itemsTotal: total,
        grandTotal: total,
        isGuest: true);
  }
}

final guestCartStoreProvider = Provider((ref) => GuestCartStore(ref));

final isAuthenticatedProvider = Provider<bool>((ref) =>
    ref.watch(authSessionProvider).maybeWhen(data: (v) => v, orElse: () => false));

class CartController extends AsyncNotifier<CartData> {
  GuestCartStore get _guest => ref.read(guestCartStoreProvider);
  CartRepository get _repo => ref.read(cartRepositoryProvider);
  bool get _authed => ref.read(isAuthenticatedProvider);

  @override
  Future<CartData> build() async {
    watchScope(ref);
    ref.watch(prefsProvider);
    final authed = await ref.watch(authSessionProvider.future);
    if (!authed) return _guest.toCart(_guest.read());
    return _repo.get();
  }

  Future<void> _apply(Future<CartData> Function() op) async {
    final next = await op();
    state = AsyncData(next);
  }

  /// Adds a product. Guests keep it on the device; signed-in users hit the server.
  Future<void> add(ProductItem p, {int? variantId, int qty = 1}) async {
    if (_authed) return _apply(() => _repo.add(slug: p.slug, variantId: variantId, qty: qty));
    final items = _guest.read();
    final i = items.indexWhere(
        (e) => e['slug'] == p.slug && e['variant_id'] == variantId);
    if (i >= 0) {
      items[i]['qty'] = ((items[i]['qty'] as num?) ?? 1).toInt() + qty;
    } else {
      items.add({
        'slug': p.slug,
        'variant_id': variantId,
        'qty': qty,
        'name': p.name,
        'image': p.image,
        'price': p.price,
        if (int.tryParse(p.id) != null) 'product_id': int.parse(p.id),
      });
    }
    await _guest.write(items);
    state = AsyncData(_guest.toCart(items));
  }

  Future<void> setQty(CartLine line, int qty) async {
    if (qty < 1) return remove(line);
    if (_authed) return _apply(() => _repo.update(line.key, qty));
    final items = _guest.read();
    for (final e in items) {
      if ('${e['slug']}:${e['variant_id'] ?? 0}' == line.key) e['qty'] = qty;
    }
    await _guest.write(items);
    state = AsyncData(_guest.toCart(items));
  }

  Future<void> remove(CartLine line) async {
    if (_authed) return _apply(() => _repo.remove(line.key));
    final items = _guest.read()
      ..removeWhere((e) => '${e['slug']}:${e['variant_id'] ?? 0}' == line.key);
    await _guest.write(items);
    state = AsyncData(_guest.toCart(items));
  }

  Future<void> applyCoupon(String code) async {
    await _repo.applyCoupon(code);
    await _apply(_repo.get);
  }

  Future<void> removeCoupon() async {
    await _repo.removeCoupon();
    await _apply(_repo.get);
  }

  Future<void> reload() async {
    ref.invalidateSelf();
    await future;
  }

  /// Called right after login/register: pushes the device cart to the server.
  Future<void> mergeGuestAfterLogin() async {
    await future; // wait for the post-login rebuild (server cart)
    final items = _guest.read();
    if (items.isEmpty) return;
    final payload = [
      for (final i in items)
        {
          if (i['product_id'] != null) 'product_id': i['product_id'] else 'slug': i['slug'],
          'variant_id': i['variant_id'],
          'qty': i['qty'],
        }
    ];
    final merged = await _repo.merge(payload);
    await _guest.write(const []);
    state = AsyncData(merged);
  }
}

final cartProvider =
    AsyncNotifierProvider<CartController, CartData>(CartController.new);
final cartCountProvider = Provider<int>(
    (ref) => ref.watch(cartProvider).maybeWhen(data: (c) => c.count, orElse: () => 0));

// ------------------------------------------------------------ favorites ----

class FavoriteIds extends AsyncNotifier<Set<String>> {
  @override
  Future<Set<String>> build() async {
    watchScope(ref);
    if (!await ref.watch(authSessionProvider.future)) return <String>{};
    return ref.read(accountRepositoryProvider).favoriteIds();
  }

  /// Toggles and returns the new state. Callers must be signed in.
  Future<bool> toggle(String productId) async {
    final id = int.tryParse(productId);
    if (id == null) return false;
    final fav = await ref.read(accountRepositoryProvider).toggleFavorite(id);
    final cur = {...(state.value ?? <String>{})};
    fav ? cur.add(productId) : cur.remove(productId);
    state = AsyncData(cur);
    ref.invalidate(favoritesListProvider);
    return fav;
  }
}

final favoriteIdsProvider =
    AsyncNotifierProvider<FavoriteIds, Set<String>>(FavoriteIds.new);

final favoritesListProvider = FutureProvider<List<ProductItem>>((ref) {
  watchScope(ref);
  return ref.watch(accountRepositoryProvider).favorites();
});

// -------------------------------------------------------- account data ----

final profileProvider = FutureProvider<Map<String, dynamic>>((ref) {
  watchScope(ref);
  return ref.watch(accountRepositoryProvider).profile();
});
final addressesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) {
  watchScope(ref);
  return ref.watch(accountRepositoryProvider).addresses();
});
final ordersProvider =
    FutureProvider.family<List<OrderSummary>, String>((ref, status) {
  watchScope(ref);
  return ref.watch(ordersRepositoryProvider).orders(status: status);
});
final orderProvider = FutureProvider.family<OrderDetail, String>((ref, uuid) {
  watchScope(ref);
  return ref.watch(ordersRepositoryProvider).order(uuid);
});
final trackingProvider = FutureProvider.family<Tracking, String>((ref, uuid) {
  watchScope(ref);
  return ref.watch(ordersRepositoryProvider).tracking(uuid);
});
final returnsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) {
  watchScope(ref);
  return ref.watch(ordersRepositoryProvider).returns();
});
final returnDetailProvider =
    FutureProvider.family<Map<String, dynamic>, int>((ref, id) {
  watchScope(ref);
  return ref.watch(ordersRepositoryProvider).returnDetail(id);
});

// ------------------------------------------------------------- checkout ----

String newIdempotencyKey() {
  final r = Random.secure();
  return List.generate(24, (_) => r.nextInt(16).toRadixString(16)).join();
}

class CheckoutState {
  const CheckoutState(
      {this.gateways = const [],
      this.gateway,
      this.addressId,
      this.countryId,
      this.quote,
      this.loading = true,
      this.placing = false,
      this.error,
      this.priceChanged = false,
      this.placed,
      this.idempotencyKey = ''});
  final List<Gateway> gateways;
  final Gateway? gateway;
  final int? addressId, countryId;
  final CartData? quote;
  final bool loading, placing, priceChanged;
  final String? error;
  final PlacedOrder? placed;
  final String idempotencyKey;

  bool get shippingOk => quote?.shipping?.available ?? false;
  bool get canPlace =>
      !loading &&
      !placing &&
      gateway != null &&
      addressId != null &&
      quote != null &&
      !quote!.isEmpty &&
      !quote!.hasUnavailable &&
      shippingOk;

  CheckoutState copyWith(
          {List<Gateway>? gateways,
          Gateway? gateway,
          int? addressId,
          int? countryId,
          CartData? quote,
          bool? loading,
          bool? placing,
          String? error,
          bool clearError = false,
          bool? priceChanged,
          PlacedOrder? placed,
          String? idempotencyKey}) =>
      CheckoutState(
          gateways: gateways ?? this.gateways,
          gateway: gateway ?? this.gateway,
          addressId: addressId ?? this.addressId,
          countryId: countryId ?? this.countryId,
          quote: quote ?? this.quote,
          loading: loading ?? this.loading,
          placing: placing ?? this.placing,
          error: clearError ? null : (error ?? this.error),
          priceChanged: priceChanged ?? this.priceChanged,
          placed: placed ?? this.placed,
          idempotencyKey: idempotencyKey ?? this.idempotencyKey);
}

class CheckoutController extends Notifier<CheckoutState> {
  CheckoutRepository get _repo => ref.read(checkoutRepositoryProvider);

  @override
  CheckoutState build() {
    watchScope(ref); // tenant switch => brand-new checkout state
    return CheckoutState(idempotencyKey: newIdempotencyKey());
  }

  Future<void> init({int? preferredAddress, int? countryId}) async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final gws = await _repo.gateways();
      state = state.copyWith(
          gateways: gws,
          gateway: state.gateway ?? (gws.isNotEmpty ? gws.first : null));
      if (preferredAddress != null) {
        await selectAddress(preferredAddress, countryId);
      } else {
        final q = await _repo.quote();
        state = state.copyWith(quote: q, loading: false);
      }
    } on AppException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<void> selectAddress(int id, int? countryId) async {
    state = state.copyWith(
        addressId: id,
        countryId: countryId,
        loading: true,
        clearError: true,
        idempotencyKey: newIdempotencyKey());
    try {
      final q = await _repo.quote(addressId: id, countryId: countryId);
      state = state.copyWith(quote: q, loading: false);
    } on AppException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  void selectGateway(Gateway g) =>
      state = state.copyWith(gateway: g, idempotencyKey: newIdempotencyKey());

  void ackPriceChange() => state = state.copyWith(priceChanged: false);

  /// Places the order. Never reports success unless the server returned 201.
  Future<PlacedOrder?> place(Map<String, dynamic> shipping) async {
    final s = state;
    if (!s.canPlace) return null;
    state = s.copyWith(placing: true, clearError: true);
    try {
      final order = await _repo.place({
        'payment_method': s.gateway!.code,
        'payment_gateway_id': s.gateway!.id,
        'expected_total': s.quote!.grandTotal,
        'shipping': {...shipping, 'address_id': s.addressId, 'country_id': s.countryId},
      }, idempotencyKey: s.idempotencyKey);
      state = state.copyWith(placing: false, placed: order);
      ref.invalidate(cartProvider);
      return order;
    } on ApiException catch (e) {
      if (e.code == 'price_changed' && e.data is Map) {
        state = state.copyWith(
            placing: false,
            priceChanged: true,
            quote: CartData.fromJson(asMap(e.data)),
            idempotencyKey: newIdempotencyKey());
      } else {
        state = state.copyWith(placing: false, error: e.message);
      }
    } on AppException catch (e) {
      // network failure: keep the idempotency key so a retry cannot double-order
      state = state.copyWith(placing: false, error: e.message);
    }
    return null;
  }
}

final checkoutProvider =
    NotifierProvider<CheckoutController, CheckoutState>(CheckoutController.new);

// ---------------------------------------------------------- auth actions ----

class AuthActions {
  AuthActions(this._ref);
  final Ref _ref;

  String get _tenant => _ref.read(tenantControllerProvider).tenant!.id;

  Future<void> _afterAuth() async {
    _ref.invalidate(authSessionProvider);
    await _ref.read(authSessionProvider.future);
    try {
      await _ref.read(cartProvider.notifier).mergeGuestAfterLogin();
    } on AppException {
      // keep the device cart; the user can retry from the cart screen
    }
  }

  Future<void> login(String email, String password) async {
    await _ref.read(authRepositoryProvider).login(_tenant, email, password);
    await _afterAuth();
  }

  Future<void> register(Map<String, dynamic> fields) async {
    await _ref.read(authRepositoryProvider).register(_tenant, fields);
    await _afterAuth();
  }

  Future<void> logout() async {
    await _ref.read(authRepositoryProvider).logout(_tenant);
    _ref.invalidate(authSessionProvider);
    _ref.read(sessionEpochProvider.notifier).bump(); // drop all account data
  }

  Future<void> deleteAccount(String password) async {
    await _ref.read(accountRepositoryProvider).deleteAccount(password);
    await logout();
  }
}

final authActionsProvider = Provider((ref) => AuthActions(ref));

/// Locale for MaterialApp (null until a tenant is active).
final appLocaleProvider = Provider<Locale>(
    (ref) => Locale(ref.watch(prefsProvider.select((p) => p.locale)).split(RegExp('[-_]')).first));
