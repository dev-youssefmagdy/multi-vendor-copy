import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/app.dart';
import 'package:application/app/providers.dart';
import 'package:application/app/router.dart';
import 'package:application/core/config/env.dart';
import 'package:application/core/network/api_client.dart';
import 'package:application/core/platform/external_links.dart';
import 'package:application/features/startup/tenant_state.dart';
import 'package:application/features/tenant/tenant_models.dart';

import '../support/harness.dart';

/// White-label (TENANT_HOST) builds: locked to one store.
const lockA = TenantBinding(
  id: 'shopa',
  host: 'shop-a.example.com',
  name: 'Shop A App',
  linkHosts: ['www.shop-a.com'],
);

Future<(Harness, RecordingLinkOpener)> whiteLabel({
  TenantBinding binding = lockA,
  Uri? initialLink,
  Map<String, Object> prefs = const {},
}) async {
  final opener = RecordingLinkOpener();
  final h = await Harness.create(
    initialLink: initialLink,
    initialPrefs: prefs,
    extraOverrides: [
      appConfigProvider.overrideWithValue(AppConfig(tenant: binding)),
      externalLinkOpenerProvider.overrideWithValue(opener),
    ],
  );
  return (h, opener);
}

const storedShopB = {
  'app:active_tenant': 'b2',
  'tenant:b2:tenant': '{"tenant_id":"b2","domain":"shop-b.example.com","name":"Shop B","status":"active"}',
};

void main() {
  group('white-label lock', () {
    test(
      'first launch opens the build-time store, never store entry',
      () async {
        final (h, _) = await whiteLabel();
        await h.container.read(tenantControllerProvider.notifier).start();
        final s = h.container.read(tenantControllerProvider);
        expect(s.phase, StartupPhase.ready);
        expect(s.tenant!.id, 'a1');
        expect(
          h.container.read(apiSessionProvider).tenant!.host,
          'shop-a.example.com',
        );
      },
    );

    test('a different stored store is ignored', () async {
      final (h, _) = await whiteLabel(prefs: storedShopB);
      await h.container.read(tenantControllerProvider.notifier).start();
      expect(h.container.read(tenantControllerProvider).tenant!.id, 'a1');
    });

    test('launch link to another store opens the browser, app stays', () async {
      final (h, opener) = await whiteLabel(
        initialLink: Uri.parse('https://shop-b.example.com/products/42'),
      );
      await h.container.read(tenantControllerProvider.notifier).start();
      final s = h.container.read(tenantControllerProvider);
      expect(s.tenant!.id, 'a1');
      expect(s.pendingLocation, isNull);
      expect(opener.opened, [
        Uri.parse('https://shop-b.example.com/products/42'),
      ]);
    });

    test('launch link to own host (or its custom domain) navigates', () async {
      final (h, opener) = await whiteLabel(
        initialLink: Uri.parse('https://www.shop-a.com/products/7'),
      );
      await h.container.read(tenantControllerProvider.notifier).start();
      final s = h.container.read(tenantControllerProvider);
      expect(s.tenant!.id, 'a1');
      expect(s.pendingLocation, '/product/7');
      expect(opener.opened, isEmpty);
    });

    test(
      'running: other-store link does not switch; own link navigates',
      () async {
        final (h, opener) = await whiteLabel();
        final c = h.container.read(tenantControllerProvider.notifier);
        await c.start();
        final calls = h.repo.resolveCalls;
        final norm = h.container.read(urlNormalizerProvider);

        await c.handleDeepLink(
          norm.parseUri(Uri.parse('https://shop-b.example.com/checkout'))!,
        );
        var s = h.container.read(tenantControllerProvider);
        expect(s.tenant!.id, 'a1');
        expect(s.phase, StartupPhase.ready);
        expect(h.repo.resolveCalls, calls);
        expect(opener.opened.single.host, 'shop-b.example.com');

        // ?store= handoff to another store is also refused.
        await c.handleDeepLink(
          norm.parseUri(
            Uri.parse(
              'demoshop://shop-a.example.com/cart?store=shop-b.example.com',
            ),
          )!,
        );
        expect(h.container.read(tenantControllerProvider).tenant!.id, 'a1');
        expect(
          opener.opened.last,
          Uri.parse('https://shop-b.example.com/cart'),
        );

        await c.handleDeepLink(
          norm.parseUri(Uri.parse('https://shop-a.example.com/cart'))!,
        );
        s = h.container.read(tenantControllerProvider);
        expect(s.pendingLocation, '/cart');
        expect(h.repo.resolveCalls, calls);
      },
    );

    test('store entry, recents and change store are disabled', () async {
      final (h, _) = await whiteLabel();
      final c = h.container.read(tenantControllerProvider.notifier);
      await c.start();
      c.changeStore();
      expect(
        h.container.read(tenantControllerProvider).phase,
        StartupPhase.ready,
      );
      await c.submitInput('shop-b.example.com');
      await c.selectRecent(shopB);
      expect(h.container.read(tenantControllerProvider).tenant!.id, 'a1');
    });

    test('unknown store -> invalid; retry re-opens the same store', () async {
      final (h, _) = await whiteLabel(
        binding: const TenantBinding(id: 'ghost', host: 'ghost.example.com'),
      );
      final c = h.container.read(tenantControllerProvider.notifier);
      await c.start();
      expect(
        h.container.read(tenantControllerProvider).phase,
        StartupPhase.invalid,
      );
      h.repo.add(
        const Tenant(id: 'g9', domain: 'ghost.example.com', name: 'Ghost'),
      );
      await c.retry();
      expect(
        h.container.read(tenantControllerProvider).phase,
        StartupPhase.ready,
      );
    });
  });

  group('platform build unchanged', () {
    test(
      'no binding -> store entry on first launch, links switch stores',
      () async {
        final h = await Harness.create(
          extraOverrides: [
            appConfigProvider.overrideWithValue(
              AppConfig(tenant: TenantBinding.none),
            ),
          ],
        );
        final c = h.container.read(tenantControllerProvider.notifier);
        await c.start();
        expect(
          h.container.read(tenantControllerProvider).phase,
          StartupPhase.needsStore,
        );
        await c.submitInput('shop-a.example.com');
        await c.handleDeepLink(
          h.container
              .read(urlNormalizerProvider)
              .parseUri(Uri.parse('https://shop-b.example.com/cart'))!,
        );
        expect(h.container.read(tenantControllerProvider).tenant!.id, 'b2');
      },
    );

    test('default AppConfig in tests is the generic app', () {
      expect(AppConfig().tenant.isWhiteLabel, isFalse);
      expect(AppConfig().urlScheme, 'storeapp');
    });

    test('requests announce the binary URL scheme (X-App-Scheme)', () async {
      final o = RequestOptions(path: '/x');
      await HeaderInterceptor(
        AppConfig(urlScheme: 'shopa'),
        ApiSession(),
        (_) async => null,
      ).onRequest(o, RequestInterceptorHandler());
      expect(o.headers['X-App-Scheme'], 'shopa');
    });
  });

  group('TenantBinding.validate (startup fail-fast)', () {
    test('consistent bindings pass', () {
      expect(TenantBinding.none.validate(), isNull);
      expect(TenantBinding.none.validate(flavor: 'platform'), isNull);
      expect(
        const TenantBinding(id: 'platform').validate(flavor: 'platform'),
        isNull,
      );
      expect(lockA.validate(flavor: 'shopa'), isNull);
    });
    test('mismatches fail', () {
      expect(
        TenantBinding.none.validate(flavor: 'demo'),
        contains('flavor "demo"'),
      );
      expect(lockA.validate(flavor: 'demo'), isNotNull);
      expect(
        const TenantBinding(id: 'demo').validate(),
        contains('without TENANT_HOST'),
      );
      expect(const TenantBinding(host: 'a.example.com').validate(), isNotNull);
      expect(
        const TenantBinding(id: 'demo', host: 'bad host').validate(),
        contains('Invalid'),
      );
      expect(const TenantBinding(id: 'Bad!').validate(), contains('TENANT_ID'));
    });
    test('owns its host and custom domains only', () {
      expect(lockA.ownsHost('SHOP-A.example.com'), isTrue);
      expect(lockA.ownsHost('www.shop-a.com'), isTrue);
      expect(lockA.ownsHost('shop-b.example.com'), isFalse);
    });
  });

  group('white-label UI', () {
    Future<void> pump(WidgetTester t, Harness h) async {
      await t.pumpWidget(
        UncontrolledProviderScope(
          container: h.container,
          child: const MobileApp(),
        ),
      );
      await t.pumpAndSettle();
    }

    testWidgets('opens straight into the store', (t) async {
      final (h, _) = await whiteLabel();
      await pump(t, h);
      expect(find.text('Find your store'), findsNothing);
      expect(find.byKey(const Key('home_marker')), findsOneWidget);
    });

    testWidgets('invalid store offers retry, not change store', (t) async {
      final (h, _) = await whiteLabel(
        binding: const TenantBinding(id: 'ghost', host: 'ghost.example.com'),
      );
      await pump(t, h);
      expect(find.text('Store not found'), findsOneWidget);
      expect(find.byKey(const Key('change_store')), findsNothing);
      expect(find.byKey(const Key('retry')), findsOneWidget);
      await t.tap(find.byKey(const Key('retry')));
      await t.pumpAndSettle();
      expect(find.text('Find your store'), findsNothing);
    });

    testWidgets('offline store offers no change store', (t) async {
      final (h, _) = await whiteLabel();
      h.repo.offline = true;
      await pump(t, h);
      expect(find.text('You are offline'), findsOneWidget);
      expect(find.text('Change store'), findsNothing);
    });

    testWidgets('/store-entry does not exist in a white-label build', (
      t,
    ) async {
      final (h, _) = await whiteLabel();
      await pump(t, h);
      h.container.read(routerProvider).go('/store-entry');
      await t.pumpAndSettle();
      expect(find.text('Find your store'), findsNothing);
    });
  });
}
