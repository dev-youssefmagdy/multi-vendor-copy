import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_riverpod/misc.dart' show Override;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:application/app/providers.dart';
import 'package:application/core/network/connectivity_service.dart';
import 'package:application/core/storage/key_value_store.dart';
import 'package:application/features/auth/auth_repository.dart';
import 'package:application/features/deeplink/deep_link_service.dart';
import 'package:application/features/tenant/mock_tenant_repository.dart';
import 'package:application/features/tenant/tenant_models.dart';
import 'package:application/features/tenant/tenant_repository.dart';

const shopA = Tenant(id: 'a1', domain: 'shop-a.example.com', name: 'Shop A');
const shopB = Tenant(id: 'b2', domain: 'shop-b.example.com', name: 'Shop B');
const shopS = Tenant(
    id: 's3',
    domain: 'sus.example.com',
    name: 'Suspended',
    status: TenantStatus.suspended);

class Harness {
  Harness._(this.prefs, this.secure, this.repo, this.links, this.container);
  final SharedPreferences prefs;
  final InMemorySecureStore secure;
  final MockTenantRepository repo;
  final FakeDeepLinkSource links;
  final ProviderContainer container;

  static Future<Harness> create({
    Map<String, Object> initialPrefs = const {},
    InMemorySecureStore? secure,
    MockTenantRepository? repo,
    Uri? initialLink,
  }) async {
    SharedPreferences.setMockInitialValues(initialPrefs);
    final prefs = await SharedPreferences.getInstance();
    final s = secure ?? InMemorySecureStore();
    final r = repo ??
        (MockTenantRepository()
          ..add(shopA,
              code: 'shopa',
              config: const TenantConfig(
                  appName: 'Shop A',
                  themeSlug: 'elora',
                  variantKey: 'v2',
                  colors: {'primary': '#ff0000'}))
          ..add(shopB, code: 'shopb')
          ..add(shopS));
    final l = FakeDeepLinkSource(initialLink);
    final c = ProviderContainer(overrides: overrides(prefs, s, r, l));
    return Harness._(prefs, s, r, l, c);
  }

  static List<Override> overrides(SharedPreferences prefs, InMemorySecureStore s,
          MockTenantRepository r, FakeDeepLinkSource l,
          {bool includeAuth = true}) =>
      [
        sharedPreferencesProvider.overrideWithValue(prefs),
        secureStoreProvider.overrideWithValue(s),
        tenantRepositoryProvider.overrideWithValue(r as TenantRepository),
        deepLinkSourceProvider.overrideWithValue(l),
        connectivityProvider.overrideWithValue(FakeConnectivityService()),
        if (includeAuth)
          authRepositoryProvider.overrideWith((ref) =>
              MockAuthRepository(ref.watch(tokenVaultProvider))),
      ];
}
