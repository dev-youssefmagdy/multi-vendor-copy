import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/storage/key_value_store.dart';
import 'tenant_models.dart';

/// Persists tenant data. Every tenant-owned value is namespaced
/// `tenant:<id>:<key>`; only the active pointer and recents are global.
class TenantStorageService {
  TenantStorageService(this._prefs, this._secure);
  final SharedPreferences _prefs;
  final SecureStore _secure;

  static const _activeKey = 'app:active_tenant';
  static const _recentKey = 'app:recent_stores';
  static const maxRecent = 8;

  Tenant? loadActiveTenant() {
    final id = _prefs.getString(_activeKey);
    if (id == null) return null;
    return loadTenant(id);
  }

  String? get activeTenantId => _prefs.getString(_activeKey);

  Tenant? loadTenant(String id) {
    final s = _prefs.getString(tenantKey(id, 'tenant'));
    if (s == null) return null;
    try {
      return Tenant.fromJson(jsonDecode(s) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  Future<void> saveActiveTenant(Tenant t) async {
    await _prefs.setString(tenantKey(t.id, 'tenant'), jsonEncode(t.toJson()));
    await _prefs.setString(_activeKey, t.id);
    final recents = loadRecent().where((r) => r.id != t.id).toList()
      ..insert(0, t);
    await _prefs.setStringList(
      _recentKey,
      [for (final r in recents.take(maxRecent)) jsonEncode(r.toJson())],
    );
  }

  Future<void> clearActive() => _prefs.remove(_activeKey);

  List<Tenant> loadRecent() {
    final out = <Tenant>[];
    for (final s in _prefs.getStringList(_recentKey) ?? const <String>[]) {
      try {
        out.add(Tenant.fromJson(jsonDecode(s) as Map<String, dynamic>));
      } catch (_) {}
    }
    return out;
  }

  Future<void> saveConfig(String tenantId, TenantConfig c, String? etag) async {
    await _prefs.setString(tenantKey(tenantId, 'bootstrap'), jsonEncode(c.toJson()));
    if (etag != null) {
      await _prefs.setString(tenantKey(tenantId, 'etag'), etag);
    }
  }

  TenantConfig? loadConfig(String tenantId) {
    final s = _prefs.getString(tenantKey(tenantId, 'bootstrap'));
    if (s == null) return null;
    try {
      return TenantConfig.fromJson(jsonDecode(s) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  String? loadEtag(String tenantId) =>
      _prefs.getString(tenantKey(tenantId, 'etag'));

  /// Generic per-tenant prefs helpers for feature caches.
  String? getString(String tenantId, String key) =>
      _prefs.getString(tenantKey(tenantId, key));
  Future<void> setString(String tenantId, String key, String v) =>
      _prefs.setString(tenantKey(tenantId, key), v);

  /// Removes every value owned by a tenant (prefs + secure tokens).
  Future<void> wipeTenant(String tenantId) async {
    final prefix = tenantKey(tenantId, '');
    for (final k in _prefs.getKeys().where((k) => k.startsWith(prefix)).toList()) {
      await _prefs.remove(k);
    }
    await _secure.delete(tenantKey(tenantId, 'token'));
    await _secure.delete(tenantKey(tenantId, 'refresh_token'));
    if (activeTenantId == tenantId) await clearActive();
    final recents = loadRecent().where((r) => r.id != tenantId);
    await _prefs.setStringList(
        _recentKey, [for (final r in recents) jsonEncode(r.toJson())]);
  }
}
