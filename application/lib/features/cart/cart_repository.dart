import '../../core/network/api_client.dart';
import '../shop/commerce_models.dart';
import '../shop/shop_repository.dart';

/// Server cart (login required) + coupon.
abstract class CartRepository {
  Future<CartData> get();
  Future<CartData> add({required String slug, int? variantId, int qty = 1});
  Future<CartData> update(String key, int qty);
  Future<CartData> remove(String key);
  Future<CartData> merge(List<Map<String, dynamic>> items);
  Future<void> applyCoupon(String code);
  Future<void> removeCoupon();
}

class RemoteCartRepository implements CartRepository {
  RemoteCartRepository(this._api);
  final ApiClient _api;

  Future<CartData> _post(String path, Map<String, dynamic> body) async =>
      CartData.fromJson(asMap(await _api.request(path, method: 'POST', body: body)));

  @override
  Future<CartData> get() async =>
      CartData.fromJson(asMap(await _api.request('/cart')));
  @override
  Future<CartData> add({required String slug, int? variantId, int qty = 1}) =>
      _post('/cart/add', {'slug': slug, 'variant_id': ?variantId, 'qty': qty});
  @override
  Future<CartData> update(String key, int qty) =>
      _post('/cart/update', {'key': key, 'qty': qty});
  @override
  Future<CartData> remove(String key) => _post('/cart/remove', {'key': key});
  @override
  Future<CartData> merge(List<Map<String, dynamic>> items) =>
      _post('/cart/merge', {'items': items});
  @override
  Future<void> applyCoupon(String code) async =>
      _api.request('/coupon/apply', method: 'POST', body: {'code': code});
  @override
  Future<void> removeCoupon() async =>
      _api.request('/coupon/remove', method: 'POST');
}
