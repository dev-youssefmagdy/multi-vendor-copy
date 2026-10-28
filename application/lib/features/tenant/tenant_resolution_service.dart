import '../deeplink/url_normalizer.dart';
import 'tenant_models.dart';
import 'tenant_repository.dart';

/// Turns raw user input / deep-link hosts into a resolve call.
class TenantResolutionService {
  TenantResolutionService(this._repo, [this._normalizer = const UrlNormalizer()]);
  final TenantRepository _repo;
  final UrlNormalizer _normalizer;

  /// Throws [InvalidStoreInputException] for unusable input.
  Future<ResolveResult> resolveInput(String raw) {
    final c = _normalizer.classifyInput(raw);
    return c.isCode
        ? _repo.resolve(code: c.value)
        : _repo.resolve(host: c.value);
  }

  Future<ResolveResult> resolveHost(String host) =>
      _repo.resolve(host: _normalizer.normalizeHost(host));
}
