import '../../core/storage/key_value_store.dart';

/// Per-tenant token storage. Tokens of one tenant are never readable via
/// another tenant's id (keys are `tenant:<id>:token`).
class TokenVault {
  TokenVault(this._secure);
  final SecureStore _secure;

  Future<String?> readAccess(String tenantId) =>
      _secure.read(tenantKey(tenantId, 'token'));
  Future<String?> readRefresh(String tenantId) =>
      _secure.read(tenantKey(tenantId, 'refresh_token'));

  Future<void> save(String tenantId,
      {required String access, String? refresh}) async {
    await _secure.write(tenantKey(tenantId, 'token'), access);
    if (refresh != null) {
      await _secure.write(tenantKey(tenantId, 'refresh_token'), refresh);
    }
  }

  Future<void> clear(String tenantId) async {
    await _secure.delete(tenantKey(tenantId, 'token'));
    await _secure.delete(tenantKey(tenantId, 'refresh_token'));
  }
}
