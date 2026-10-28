import '../../core/network/api_client.dart';
import '../../themes/shared/models.dart';
import '../shop/shop_repository.dart';

abstract class AccountRepository {
  Future<Map<String, dynamic>> profile();
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> fields);
  Future<void> changePassword(String current, String password, String confirm);
  Future<void> deleteAccount(String password);
  Future<List<Map<String, dynamic>>> addresses();
  Future<void> saveAddress(Map<String, dynamic> fields, {int? id});
  Future<void> deleteAddress(int id);
  Future<void> setDefaultAddress(int id);
  Future<List<ProductItem>> favorites();
  Future<Set<String>> favoriteIds();

  /// Returns the new favorited state.
  Future<bool> toggleFavorite(int productId);
  Future<void> submitReview(int productId, int stars, String comment);
}

class RemoteAccountRepository implements AccountRepository {
  RemoteAccountRepository(this._api);
  final ApiClient _api;

  @override
  Future<Map<String, dynamic>> profile() async =>
      asMap(asMap(await _api.request('/me'))['customer']);
  @override
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> f) async =>
      asMap(asMap(await _api.request('/profile', method: 'PUT', body: f))['customer']);
  @override
  Future<void> changePassword(String c, String p, String conf) async =>
      _api.request('/profile/password',
          method: 'POST',
          body: {'current_password': c, 'password': p, 'password_confirmation': conf});
  @override
  Future<void> deleteAccount(String password) async =>
      _api.request('/auth/account', method: 'DELETE', body: {'password': password});
  @override
  Future<List<Map<String, dynamic>>> addresses() async =>
      asList(asMap(await _api.request('/addresses'))['data']);
  @override
  Future<void> saveAddress(Map<String, dynamic> f, {int? id}) async =>
      _api.request(id == null ? '/addresses' : '/addresses/$id',
          method: id == null ? 'POST' : 'PUT', body: f);
  @override
  Future<void> deleteAddress(int id) async =>
      _api.request('/addresses/$id', method: 'DELETE');
  @override
  Future<void> setDefaultAddress(int id) async =>
      _api.request('/addresses/$id/default', method: 'POST');
  @override
  Future<List<ProductItem>> favorites() async =>
      productsOf(asMap(await _api.request('/favorites/list'))['products']);
  @override
  Future<Set<String>> favoriteIds() async {
    final d = asMap(await _api.request('/favorites/ids'));
    return {for (final i in (d['ids'] as List? ?? const [])) '$i'};
  }

  @override
  Future<bool> toggleFavorite(int productId) async {
    final d = asMap(await _api
        .request('/favorites/toggle', method: 'POST', body: {'product_id': productId}));
    return d['favorited'] == true;
  }

  @override
  Future<void> submitReview(int productId, int stars, String comment) async =>
      _api.request('/reviews',
          method: 'POST',
          body: {'product_id': productId, 'stars': stars, 'comment': comment});
}
