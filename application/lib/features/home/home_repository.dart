import '../../core/network/api_client.dart';

/// Loads `GET /home/sections/{key}` and caches per tenant + locale + currency
/// (so switching store / language never shows another tenant's data).
class HomeRepository {
  HomeRepository(this._api);
  final ApiClient _api;
  final Map<String, Map<String, dynamic>> _cache = {};

  String cacheKey(String key, {int? limit, String? tab}) {
    final t = _api.session.tenant?.tenantId ?? '-';
    return '$t|${_api.session.locale}|${_api.session.currency ?? ''}|$key|${limit ?? ''}|${tab ?? ''}';
  }

  Future<Map<String, dynamic>> section(String key,
      {int? limit, String? tab, bool refresh = false}) async {
    final ck = cacheKey(key, limit: limit, tab: tab);
    if (!refresh && _cache.containsKey(ck)) return _cache[ck]!;
    final d = await _api.request('/home/sections/$key', query: {
      'limit': ?limit,
      if (tab != null && tab.isNotEmpty) 'tab': tab,
    });
    final map = d is Map ? Map<String, dynamic>.from(d) : <String, dynamic>{};
    _cache[ck] = map;
    return map;
  }

  void clear() => _cache.clear();
}
