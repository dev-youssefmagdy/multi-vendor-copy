import '../../core/errors/app_exception.dart';
import 'tenant_models.dart';
import 'tenant_repository.dart';

/// In-memory repository for tests / previews.
class MockTenantRepository implements TenantRepository {
  final Map<String, Tenant> byHost = {};
  final Map<String, Tenant> byCode = {};
  final Map<String, TenantConfig> configs = {};
  bool offline = false;
  int resolveCalls = 0;
  int bootstrapCalls = 0;

  void add(Tenant t, {String? code, TenantConfig? config}) {
    byHost[t.domain] = t;
    if (code != null) byCode[code] = t;
    configs[t.id] = config ?? const TenantConfig(appName: 'Mock');
  }

  @override
  Future<ResolveResult> resolve({String? host, String? code}) async {
    resolveCalls++;
    if (offline) throw const NetworkException();
    final t = host != null ? byHost[host] : byCode[code];
    return t == null
        ? const ResolveResult(TenantStatus.notFound)
        : ResolveResult(t.status, t);
  }

  @override
  Future<BootstrapResult> fetchBootstrap(Tenant tenant, {String? etag}) async {
    bootstrapCalls++;
    if (offline) throw const NetworkException();
    return BootstrapResult(
        config: configs[tenant.id] ?? const TenantConfig(), etag: 'v1');
  }
}
