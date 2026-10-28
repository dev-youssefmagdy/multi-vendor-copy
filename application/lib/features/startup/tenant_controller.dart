import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../../core/errors/app_exception.dart';
import '../../core/network/api_client.dart';
import '../deeplink/url_normalizer.dart';
import '../tenant/tenant_models.dart';
import 'tenant_state.dart';

/// Owns the active tenant + startup flow:
/// deep link -> stored tenant -> validate -> bootstrap -> offline fallback.
class TenantController extends Notifier<TenantState> {
  @override
  TenantState build() => const TenantState();

  Future<void> start() async {
    state = const TenantState(phase: StartupPhase.booting);
    final link = await ref.read(deepLinkServiceProvider).initialTarget();
    if (link != null) {
      return _enter(host: link.host, pending: link.location);
    }
    final stored = ref.read(tenantStorageProvider).loadActiveTenant();
    if (stored != null) {
      return _enter(host: stored.domain, stored: stored);
    }
    state = const TenantState(phase: StartupPhase.needsStore);
  }

  /// Retry from invalid/offline screens: re-run the stored flow.
  Future<void> retry() async {
    final stored = ref.read(tenantStorageProvider).loadActiveTenant();
    if (stored != null) {
      state = const TenantState(phase: StartupPhase.booting);
      return _enter(host: stored.domain, stored: stored);
    }
    state = const TenantState(phase: StartupPhase.needsStore);
  }

  void changeStore() =>
      state = TenantState(phase: StartupPhase.needsStore, tenant: state.tenant);

  /// Store-entry submit (URL or code). Invalid text keeps the entry screen.
  Future<void> submitInput(String raw) async {
    final StoreInput input;
    try {
      input = ref.read(urlNormalizerProvider).classifyInput(raw);
    } on InvalidStoreInputException catch (e) {
      state = TenantState(
          phase: StartupPhase.needsStore, message: e.message);
      return;
    }
    state = const TenantState(phase: StartupPhase.booting);
    await _enter(
        host: input.isCode ? null : input.value,
        code: input.isCode ? input.value : null);
  }

  Future<void> selectRecent(Tenant t) => _enter(host: t.domain, viaRecent: true);

  /// Incoming link while running (same tenant -> navigate; other -> switch).
  Future<void> handleDeepLink(DeepLinkTarget t) async {
    final cur = state.tenant;
    if (state.phase == StartupPhase.ready && cur != null && cur.domain == t.host) {
      state = state.copyWith(pendingLocation: t.location);
      return;
    }
    state = const TenantState(phase: StartupPhase.booting);
    await _enter(host: t.host, pending: t.location);
  }

  void consumePending() => state = state.copyWith(clearPending: true);

  Future<void> _enter({
    String? host,
    String? code,
    String? pending,
    Tenant? stored,
    bool viaRecent = false,
  }) async {
    final storage = ref.read(tenantStorageProvider);
    final session = ref.read(apiSessionProvider);
    // Switch workflow step 1: stop all work for the previous tenant.
    ref.read(apiClientProvider).cancelAll();
    session.tenant = null;
    state = TenantState(phase: StartupPhase.booting, pendingLocation: pending);

    final res = ref.read(resolutionServiceProvider);
    ResolveResult r;
    try {
      r = host != null
          ? await res.resolveHost(host)
          : await res.resolveInput(code!);
    } on NetworkException {
      return _offlineFallback(stored ?? storage.loadTenant(storage.activeTenantId ?? ''),
          pending, host);
    } on ApiException catch (e) {
      state = TenantState(phase: StartupPhase.offline, message: e.message);
      return;
    } on InvalidStoreInputException catch (e) {
      state = TenantState(phase: StartupPhase.invalid, message: e.message);
      return;
    }

    switch (r.status) {
      case TenantStatus.notFound:
        state = const TenantState(phase: StartupPhase.invalid);
      case TenantStatus.suspended:
        state = TenantState(phase: StartupPhase.suspended, tenant: r.tenant);
      case TenantStatus.active:
        await _bootstrap(r.tenant!, pending);
    }
  }

  Future<void> _offlineFallback(
      Tenant? stored, String? pending, String? host) async {
    final storage = ref.read(tenantStorageProvider);
    // Only trust cache for the same host we were trying to open.
    if (stored != null && (host == null || stored.domain == host)) {
      final cfg = storage.loadConfig(stored.id);
      if (cfg != null) {
        _activate(stored);
        state = TenantState(
            phase: StartupPhase.ready,
            tenant: stored,
            config: cfg,
            fromCache: true,
            pendingLocation: pending);
        return;
      }
    }
    state = const TenantState(phase: StartupPhase.offline);
  }

  void _activate(Tenant t) {
    final s = ref.read(apiSessionProvider);
    final changed = s.tenant?.tenantId != t.id;
    s.tenant = TenantContext(tenantId: t.id, host: t.domain);
    if (changed) ref.read(sessionEpochProvider.notifier).bump();
  }

  Future<void> _bootstrap(Tenant t, String? pending) async {
    final storage = ref.read(tenantStorageProvider);
    final cached = storage.loadConfig(t.id);
    _activate(t);
    try {
      final b = await ref
          .read(tenantRepositoryProvider)
          .fetchBootstrap(t, etag: cached == null ? null : storage.loadEtag(t.id));
      final cfg = b.notModified ? cached : b.config;
      if (cfg == null) {
        state = const TenantState(phase: StartupPhase.offline);
        return;
      }
      if (!b.notModified) await storage.saveConfig(t.id, cfg, b.etag);
      await storage.saveActiveTenant(t);
      state = TenantState(
          phase: StartupPhase.ready,
          tenant: t,
          config: cfg,
          pendingLocation: pending);
    } on NetworkException {
      if (cached != null) {
        await storage.saveActiveTenant(t);
        state = TenantState(
            phase: StartupPhase.ready,
            tenant: t,
            config: cached,
            fromCache: true,
            pendingLocation: pending);
      } else {
        ref.read(apiSessionProvider).tenant = null;
        state = const TenantState(phase: StartupPhase.offline);
      }
    } on ApiException catch (e) {
      ref.read(apiSessionProvider).tenant = null;
      state = TenantState(
          phase: (e.statusCode == 403 || e.statusCode == 423)
              ? StartupPhase.suspended
              : (e.statusCode == 404 ? StartupPhase.invalid : StartupPhase.offline),
          tenant: t,
          message: e.message);
    }
  }
}
