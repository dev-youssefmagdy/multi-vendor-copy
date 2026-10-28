import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import 'package:application/features/startup/tenant_state.dart';
import 'package:application/features/tenant/tenant_guard.dart';
import '../support/harness.dart';

void main() {
  test('first launch -> needsStore', () async {
    final h = await Harness.create();
    await h.container.read(tenantControllerProvider.notifier).start();
    expect(h.container.read(tenantControllerProvider).phase,
        StartupPhase.needsStore);
  });

  test('submit URL -> ready, persisted; restart uses stored', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('https://Shop-A.example.com/');
    var s = h.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.ready);
    expect(s.tenant!.id, 'a1');
    expect(s.config!.themeSlug, 'elora');
    expect(h.container.read(apiSessionProvider).tenant!.tenantId, 'a1');

    // new container, same prefs => stored flow
    final h2 = await Harness.create(initialPrefs: {
      for (final k in h.prefs.getKeys()) k: h.prefs.get(k)!
    }, repo: h.repo);
    await h2.container.read(tenantControllerProvider.notifier).start();
    s = h2.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.ready);
    expect(s.tenant!.id, 'a1');
  });

  test('store code works; bad input stays on entry with message', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('bad code!');
    var s = h.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.needsStore);
    expect(s.message, isNotNull);
    await c.submitInput('shopb');
    s = h.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.ready);
    expect(s.tenant!.id, 'b2');
  });

  test('invalid and suspended', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('nope.example.com');
    expect(h.container.read(tenantControllerProvider).phase, StartupPhase.invalid);
    expect(h.container.read(apiSessionProvider).tenant, isNull);
    await c.submitInput('sus.example.com');
    expect(h.container.read(tenantControllerProvider).phase,
        StartupPhase.suspended);
  });

  test('invalid switch does not clobber stored active tenant', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    await c.submitInput('nope.example.com');
    expect(h.container.read(tenantStorageProvider).activeTenantId, 'a1');
  });

  test('deep link at launch overrides stored and sets pending', () async {
    final h = await Harness.create(
        initialLink: Uri.parse('https://shop-b.example.com/product/42'));
    await h.container.read(tenantControllerProvider.notifier).start();
    final s = h.container.read(tenantControllerProvider);
    expect(s.tenant!.id, 'b2');
    expect(s.pendingLocation, '/product/42');
  });

  test('offline: cached bootstrap -> ready(fromCache); none -> offline', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    h.repo.offline = true;
    await c.retry();
    var s = h.container.read(tenantControllerProvider);
    expect(s.phase, StartupPhase.ready);
    expect(s.fromCache, isTrue);

    final h2 = await Harness.create();
    h2.repo.offline = true;
    await h2.container.read(tenantControllerProvider.notifier).submitInput('shop-a.example.com');
    expect(h2.container.read(tenantControllerProvider).phase, StartupPhase.offline);
  });

  test('switching tenants: epoch bumps, tokens + caches isolated', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    final e1 = h.container.read(sessionEpochProvider);
    final auth = h.container.read(authRepositoryProvider);
    await auth.login('a1', 'u@x.com', 'pw');
    h.container.invalidate(authSessionProvider);
    expect(await h.container.read(authSessionProvider.future), isTrue);

    await c.submitInput('shop-b.example.com');
    expect(h.container.read(sessionEpochProvider), greaterThan(e1));
    expect(h.container.read(tenantControllerProvider).tenant!.id, 'b2');
    expect(await h.container.read(authSessionProvider.future), isFalse);
    expect(h.container.read(apiSessionProvider).tenant!.tenantId, 'b2');

    // back to A: token restored for A only
    await c.submitInput('shop-a.example.com');
    expect(await h.container.read(authSessionProvider.future), isTrue);
  });

  test('deep link while running: same tenant -> pending, other -> switch', () async {
    final h = await Harness.create();
    final c = h.container.read(tenantControllerProvider.notifier);
    await c.submitInput('shop-a.example.com');
    h.container.read(tenantControllerProvider.notifier).consumePending();
    final calls = h.repo.resolveCalls;
    await c.handleDeepLink(h.container.read(urlNormalizerProvider)
        .parseUri(Uri.parse('https://shop-a.example.com/cart'))!);
    expect(h.container.read(tenantControllerProvider).pendingLocation, '/cart');
    expect(h.repo.resolveCalls, calls);
    await c.handleDeepLink(h.container.read(urlNormalizerProvider)
        .parseUri(Uri.parse('https://shop-b.example.com/checkout'))!);
    final s = h.container.read(tenantControllerProvider);
    expect(s.tenant!.id, 'b2');
    expect(s.pendingLocation, '/checkout');
  });

  test('TenantGuard', () {
    String? r(StartupPhase p, String l) =>
        TenantGuard.redirect(TenantState(phase: p), l);
    expect(r(StartupPhase.booting, '/home'), '/splash');
    expect(r(StartupPhase.booting, '/splash'), isNull);
    expect(r(StartupPhase.needsStore, '/product/1'), '/store-entry');
    expect(r(StartupPhase.invalid, '/home'), '/invalid');
    expect(r(StartupPhase.suspended, '/home'), '/suspended');
    expect(r(StartupPhase.offline, '/home'), '/offline');
    expect(r(StartupPhase.ready, '/splash'), '/home');
    expect(r(StartupPhase.ready, '/store-entry'), '/home');
    expect(r(StartupPhase.ready, '/product/1?x=1'), isNull);
  });
}
