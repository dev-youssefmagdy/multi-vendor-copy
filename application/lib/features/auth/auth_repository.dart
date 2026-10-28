import '../../core/errors/app_exception.dart';
import '../../core/network/api_client.dart';
import 'token_vault.dart';

abstract class AuthRepository {
  Future<void> login(String tenantId, String email, String password);
  Future<void> register(String tenantId, Map<String, dynamic> fields);
  Future<void> logout(String tenantId);
  Future<bool> refresh(String tenantId);
  Future<bool> isAuthenticated(String tenantId);

  /// Always succeeds server-side (no account enumeration).
  Future<void> forgotPassword(String email);
  Future<void> resetPassword(
      {required String email,
      required String token,
      required String password,
      required String confirmation});
}

class RemoteAuthRepository implements AuthRepository {
  RemoteAuthRepository(this._api, this._vault);
  final ApiClient _api;
  final TokenVault _vault;

  Future<void> _store(String tenantId, dynamic d) async {
    final m = Map<String, dynamic>.from(d as Map);
    final token = (m['token'] ?? m['access_token']) as String?;
    if (token == null) throw const ApiException('Missing token');
    await _vault.save(tenantId,
        access: token, refresh: m['refresh_token'] as String?);
  }

  @override
  Future<void> login(String tenantId, String email, String password) async =>
      _store(
          tenantId,
          await _api.request('/auth/login',
              method: 'POST', body: {'email': email, 'password': password}));

  @override
  Future<void> register(String tenantId, Map<String, dynamic> fields) async =>
      _store(tenantId,
          await _api.request('/auth/register', method: 'POST', body: fields));

  @override
  Future<void> logout(String tenantId) async {
    try {
      await _api.request('/auth/logout', method: 'POST');
    } on AppException {
      // best effort; always drop the local token
    }
    await _vault.clear(tenantId);
  }

  @override
  Future<bool> refresh(String tenantId) async {
    final rt = await _vault.readRefresh(tenantId);
    if (rt == null) return false;
    try {
      await _store(
          tenantId,
          await _api.request('/auth/refresh',
              method: 'POST', body: {'refresh_token': rt}));
      return true;
    } on AppException {
      return false;
    }
  }

  @override
  Future<bool> isAuthenticated(String tenantId) async =>
      (await _vault.readAccess(tenantId)) != null;

  @override
  Future<void> forgotPassword(String email) async => _api.request(
      '/auth/forgot-password',
      method: 'POST',
      body: {'email': email});

  @override
  Future<void> resetPassword(
          {required String email,
          required String token,
          required String password,
          required String confirmation}) async =>
      _api.request('/auth/reset-password', method: 'POST', body: {
        'email': email,
        'token': token,
        'password': password,
        'password_confirmation': confirmation,
      });
}

class MockAuthRepository implements AuthRepository {
  MockAuthRepository(this._vault);
  final TokenVault _vault;
  bool refreshWorks = false;

  @override
  Future<void> login(String tenantId, String email, String password) =>
      _vault.save(tenantId, access: 'mock-$tenantId-$email');
  @override
  Future<void> register(String tenantId, Map<String, dynamic> fields) =>
      _vault.save(tenantId, access: 'mock-$tenantId');
  @override
  Future<void> logout(String tenantId) => _vault.clear(tenantId);
  @override
  Future<bool> refresh(String tenantId) async => refreshWorks;
  @override
  Future<bool> isAuthenticated(String tenantId) async =>
      (await _vault.readAccess(tenantId)) != null;

  @override
  Future<void> forgotPassword(String email) async {}
  @override
  Future<void> resetPassword(
          {required String email,
          required String token,
          required String password,
          required String confirmation}) async {}
}
