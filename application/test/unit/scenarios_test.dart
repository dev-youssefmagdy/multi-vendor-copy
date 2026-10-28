import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import 'package:application/features/startup/tenant_state.dart';
import '../support/harness.dart';

void main() {
  test('cleared app data: fresh install recovers via store entry, no stale token', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    await h.container.read(authRepositoryProvider).login('a1', 'u@x.com', 'pw');

    // App data wiped: empty prefs + empty secure store.
    final h2 = await Harness.create(repo: h.repo);
    final c2 = h2.container.read(tenantControllerProvider.notifier);
    await c2.start();
    expect(h2.container.read(tenantControllerProvider).phase, StartupPhase.needsStore);
    expect(h2.container.read(tokenVaultProvider).readAccess('a1'), completion(isNull));
    await c2.submitInput('shop-a.example.com');
    expect(h2.container.read(tenantControllerProvider).phase, StartupPhase.ready);
    expect(await h2.container.read(authSessionProvider.future), isFalse);
  });

  test('unregistered domain is rejected and never persisted as active', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('evil.example.org');
    expect(h.container.read(tenantControllerProvider).phase, StartupPhase.invalid);
    expect(h.container.read(apiSessionProvider).tenant, isNull);
    expect(h.container.read(tenantStorageProvider).activeTenantId, isNull);
    await c.submitInput('not a domain!');
    expect(h.container.read(tenantControllerProvider).phase, StartupPhase.needsStore);
  });

  test('tenant A cached config is never shown for tenant B (offline)', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    expect(h.container.read(tenantStorageProvider).loadConfig('b2'), isNull);
    h.repo.offline = true;
    await c.submitInput('shop-b.example.com');
    final s = h.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.offline);
    expect(s.config, isNull);
    expect(h.container.read(apiSessionProvider).tenant, isNull);
  });

  test('restore last tenant on restart, then switch away and back', () async {
    final h = await Harness.create();
    await h.container.read(tenantControllerProvider.notifier).submitInput('shop-a.example.com');
    final h2 = await Harness.create(initialPrefs: {for (final k in h.prefs.getKeys()) k: h.prefs.get(k)!}, repo: h.repo);
    await h2.container.read(tenantControllerProvider.notifier).start();
    final s = h2.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.ready);
    expect(s.tenant!.id, 'a1');
  });
}
