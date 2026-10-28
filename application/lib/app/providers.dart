import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/config/env.dart';
import '../core/logger/app_logger.dart';
import '../core/network/api_client.dart';
import '../core/network/connectivity_service.dart';
import '../core/storage/key_value_store.dart';
import '../features/auth/auth_repository.dart';
import '../features/auth/token_vault.dart';
import '../features/deeplink/deep_link_service.dart';
import '../features/deeplink/url_normalizer.dart';
import '../features/startup/tenant_controller.dart';
import '../features/startup/tenant_state.dart';
import '../features/tenant/tenant_repository.dart';
import '../features/tenant/tenant_resolution_service.dart';
import '../features/tenant/tenant_storage_service.dart';
import '../features/theme/theme_registry.dart';

final appConfigProvider = Provider<AppConfig>((_) => AppConfig());
final loggerProvider = Provider<AppLogger>((_) => AppLogger());

/// Must be overridden in `main` / tests.
final sharedPreferencesProvider = Provider<SharedPreferences>(
    (_) => throw UnimplementedError('override sharedPreferencesProvider'));
final secureStoreProvider =
    Provider<SecureStore>((_) => FlutterSecureStore());
final connectivityProvider =
    Provider<ConnectivityService>((_) => PlusConnectivityService());

final urlNormalizerProvider = Provider((_) => const UrlNormalizer());
final apiSessionProvider = Provider<ApiSession>((_) => ApiSession());

/// Incremented on every tenant switch; tenant-scoped providers `watch` it so
/// they are rebuilt (state reset) for the new tenant.
class SessionEpoch extends Notifier<int> {
  @override
  int build() => 0;
  void bump() => state++;
}

final sessionEpochProvider =
    NotifierProvider<SessionEpoch, int>(SessionEpoch.new);

final tokenVaultProvider =
    Provider((ref) => TokenVault(ref.watch(secureStoreProvider)));

final Provider<ApiClient> apiClientProvider = Provider<ApiClient>((ref) {
  final vault = ref.watch(tokenVaultProvider);
  return ApiClient(
    config: ref.watch(appConfigProvider),
    session: ref.watch(apiSessionProvider),
    logger: ref.watch(loggerProvider),
    readToken: vault.readAccess,
    refresh: (id) => ref.read(authRepositoryProvider).refresh(id),
    onUnauthorized: (id) async {
      await vault.clear(id);
      ref.invalidate(authSessionProvider);
    },
  );
});

final Provider<AuthRepository> authRepositoryProvider = Provider<AuthRepository>((ref) =>
    RemoteAuthRepository(
        ref.watch(apiClientProvider), ref.watch(tokenVaultProvider)));

final tenantStorageProvider = Provider((ref) => TenantStorageService(
    ref.watch(sharedPreferencesProvider), ref.watch(secureStoreProvider)));

final tenantRepositoryProvider = Provider<TenantRepository>(
    (ref) => RemoteTenantRepository(ref.watch(apiClientProvider)));

final resolutionServiceProvider = Provider((ref) => TenantResolutionService(
    ref.watch(tenantRepositoryProvider), ref.watch(urlNormalizerProvider)));

final deepLinkSourceProvider =
    Provider<DeepLinkSource>((_) => AppLinksSource());
final deepLinkServiceProvider = Provider((ref) => DeepLinkService(
    ref.watch(deepLinkSourceProvider), ref.watch(urlNormalizerProvider)));

final tenantControllerProvider =
    NotifierProvider<TenantController, TenantState>(TenantController.new);

/// Is the customer logged in for the ACTIVE tenant (resets on switch).
final authSessionProvider = FutureProvider<bool>((ref) async {
  ref.watch(sessionEpochProvider);
  final id = ref.watch(tenantControllerProvider.select((s) => s.tenant?.id));
  if (id == null) return false;
  return ref.watch(authRepositoryProvider).isAuthenticated(id);
});

final themeRegistryProvider = Provider((_) => ThemeRegistry());
final resolvedThemeProvider = Provider((ref) => ref
    .watch(themeRegistryProvider)
    .resolve(ref.watch(tenantControllerProvider.select((s) => s.config))));
