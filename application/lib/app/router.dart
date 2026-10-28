import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/startup/screens.dart';
import '../features/tenant/tenant_guard.dart';
import 'providers.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(tenantControllerProvider.select((s) => s.phase),
      (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  Widget placeholder(String t) => PlaceholderScreen(title: t);
  final router = GoRouter(
    initialLocation: TenantGuard.splash,
    refreshListenable: refresh,
    redirect: (context, state) => TenantGuard.redirect(
        ref.read(tenantControllerProvider), state.uri.toString()),
    routes: [
      GoRoute(path: TenantGuard.splash, builder: (_, _) => const SplashScreen()),
      GoRoute(
          path: TenantGuard.storeEntry,
          builder: (_, _) => const StoreEntryScreen()),
      GoRoute(
          path: TenantGuard.invalid, builder: (_, _) => const InvalidTenantScreen()),
      GoRoute(
          path: TenantGuard.suspended,
          builder: (_, _) => const SuspendedTenantScreen()),
      GoRoute(path: TenantGuard.offline, builder: (_, _) => const OfflineScreen()),
      GoRoute(
          path: TenantGuard.maintenance,
          builder: (_, _) => const MaintenanceScreen()),
      GoRoute(
          path: TenantGuard.updateRequired,
          builder: (_, _) => const UpdateRequiredScreen()),
      GoRoute(path: TenantGuard.home, builder: (_, _) => const HomeScreen()),
      GoRoute(
          path: '/product/:id',
          builder: (_, s) => placeholder('Product ${s.pathParameters['id']}')),
      GoRoute(
          path: '/category/:id',
          builder: (_, s) => placeholder('Category ${s.pathParameters['id']}')),
      GoRoute(path: '/cart', builder: (_, _) => placeholder('Cart')),
      GoRoute(path: '/checkout', builder: (_, _) => placeholder('Checkout')),
      GoRoute(path: '/account', builder: (_, _) => placeholder('Account')),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
