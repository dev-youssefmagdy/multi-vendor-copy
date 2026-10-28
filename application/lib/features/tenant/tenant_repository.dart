import 'package:dio/dio.dart';
import '../../core/errors/app_exception.dart';
import '../../core/network/api_client.dart';
import 'tenant_models.dart';

abstract class TenantRepository {
  Future<ResolveResult> resolve({String? host, String? code});
  Future<BootstrapResult> fetchBootstrap(Tenant tenant, {String? etag});
}

class RemoteTenantRepository implements TenantRepository {
  RemoteTenantRepository(this._api);
  final ApiClient _api;

  @override
  Future<ResolveResult> resolve({String? host, String? code}) async {
    try {
      final d = await _api.request('/resolve',
          query: {'host': ?host, 'code': ?code},
          noTenant: true);
      return ResolveResult.fromJson(Map<String, dynamic>.from(d as Map));
    } on ApiException catch (e) {
      if (e.statusCode == 404) return const ResolveResult(TenantStatus.notFound);
      rethrow;
    }
  }

  @override
  Future<BootstrapResult> fetchBootstrap(Tenant tenant, {String? etag}) async {
    // Tenant context must already be active on the ApiSession.
    final Response<dynamic> r;
    try {
      r = await _api.dio.get<dynamic>('/bootstrap',
          options: Options(
            headers: {'If-None-Match': ?etag},
            validateStatus: (s) => s != null && (s < 300 || s == 304),
          ));
    } on DioException catch (e) {
      throw ApiClient.mapError(e);
    }
    if (r.statusCode == 304) return const BootstrapResult(notModified: true);
    final data = ApiClient.unwrap(r.data);
    final cfg = TenantConfig.fromJson(Map<String, dynamic>.from(data as Map));
    return BootstrapResult(
        config: cfg, etag: r.headers.value('etag') ?? cfg.configVersion);
  }
}
