import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/account/account_screens.dart';
import '../features/auth/auth_screens.dart';
import '../features/cart/cart_screen.dart';
import '../features/checkout/checkout_screen.dart';
import '../features/checkout/payment_screens.dart';
import '../features/home/home_screen.dart';
import '../features/orders/orders_screens.dart';
import '../features/orders/returns_screens.dart';
import '../features/shop/screens/listing_screen.dart';
import '../features/shop/screens/misc_screens.dart';
import '../features/shop/screens/product_screen.dart';
import '../features/shop/screens/search_screen.dart';
import '../features/startup/screens.dart';
import '../features/tenant/tenant_guard.dart';
import 'providers.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(tenantControllerProvider.select((s) => s.phase),
      (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  final router = GoRouter(
    initialLocation: TenantGuard.splash,
    refreshListenable: refresh,
    redirect: (context, state) => TenantGuard.redirect(
        ref.read(tenantControllerProvider), state.uri.toString()),
    errorBuilder: (_, _) => const NotFoundScreen(),
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

      // Catalog
      GoRoute(
          path: '/category/all', builder: (_, _) => const CategoriesScreen()),
      GoRoute(
          path: '/category/:id',
          builder: (_, s) =>
              ListingScreen(queryForSlug(s.pathParameters['id']!), navIndex: 1)),
      GoRoute(
          path: '/list/:name',
          builder: (_, s) => ListingScreen(queryForSlug(s.pathParameters['name']!))),
      GoRoute(
          path: '/search',
          builder: (_, s) =>
              SearchScreen(initial: s.uri.queryParameters['q'] ?? '')),
      GoRoute(
          path: '/product/:id',
          builder: (_, s) => ProductScreen(s.pathParameters['id']!)),
      GoRoute(
          path: '/page/:slug',
          builder: (_, s) => CmsPageScreen(s.pathParameters['slug']!)),

      // Cart / checkout / payment
      GoRoute(path: '/cart', builder: (_, _) => const CartScreen()),
      GoRoute(path: '/checkout', builder: (_, _) => const CheckoutScreen()),
      GoRoute(
          path: '/payment/result',
          builder: (_, s) => PaymentResultScreen(
              s.uri.queryParameters['order'] ?? '',
              hint: s.uri.queryParameters['status'])),
      GoRoute(
          path: '/payment/:uuid',
          builder: (_, s) => PaymentScreen(s.pathParameters['uuid']!)),

      // Auth
      GoRoute(
          path: '/login',
          builder: (_, s) => LoginScreen(from: s.uri.queryParameters['from'])),
      GoRoute(
          path: '/register',
          builder: (_, s) => RegisterScreen(from: s.uri.queryParameters['from'])),
      GoRoute(
          path: '/forgot-password',
          builder: (_, _) => const ForgotPasswordScreen()),

      // Account
      GoRoute(path: '/account', builder: (_, _) => const AccountScreen()),
      GoRoute(
          path: '/account/profile', builder: (_, _) => const ProfileScreen()),
      GoRoute(
          path: '/account/addresses',
          builder: (_, _) => const AddressesScreen()),
      GoRoute(
          path: '/account/preferences',
          builder: (_, _) => const PreferencesScreen()),
      GoRoute(path: '/favorites', builder: (_, _) => const FavoritesScreen()),

      // Orders / returns
      GoRoute(path: '/orders', builder: (_, _) => const OrdersScreen()),
      GoRoute(
          path: '/orders/:uuid',
          builder: (_, s) => OrderDetailScreen(s.pathParameters['uuid']!,
              justPlaced: s.uri.queryParameters['placed'] == '1')),
      GoRoute(
          path: '/orders/:uuid/tracking',
          builder: (_, s) => TrackingScreen(s.pathParameters['uuid']!)),
      GoRoute(
          path: '/orders/:uuid/invoice',
          builder: (_, s) => InvoiceScreen(s.pathParameters['uuid']!)),
      GoRoute(
          path: '/orders/:uuid/return',
          builder: (_, s) => ReturnRequestScreen(s.pathParameters['uuid']!)),
      GoRoute(path: '/returns', builder: (_, _) => const ReturnsScreen()),
      GoRoute(
          path: '/returns/:id',
          builder: (_, s) => ReturnDetailScreen(
              int.tryParse(s.pathParameters['id']!) ?? 0)),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
