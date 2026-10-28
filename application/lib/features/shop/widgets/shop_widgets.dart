import 'package:flutter/material.dart';
import '../../../design/design.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../app/providers.dart';
import '../../../core/errors/app_exception.dart';
import '../../../core/l10n/strings.dart';
import '../../../themes/shared/chrome.dart';
import '../../../themes/shared/models.dart';
import '../../../themes/shared/product_card.dart';
import '../../../themes/shared/skin.dart';
import '../commerce_providers.dart';

const _tabRoutes = {'/home', '/cart', '/favorites', '/account', '/category/all'};

/// Navigation used by every themed widget (`SkinScope.open`).
void openRoute(BuildContext context, String route) {
  ScaffoldMessenger.maybeOf(context)?.hideCurrentSnackBar();
  var path = route;
  final u = Uri.tryParse(route);
  if (u != null && u.hasScheme) path = u.path;
  if (!path.startsWith('/')) return;
  path = path
      .replaceFirst(RegExp(r'^/products/'), '/product/')
      .replaceFirst(RegExp(r'^/categories/'), '/category/');
  final router = GoRouter.maybeOf(context);
  if (router == null) return;
  ScaffoldMessenger.maybeOf(context)?.hideCurrentSnackBar();
  if (_tabRoutes.contains(path.split('?').first)) {
    router.go(path);
  } else {
    router.push(path);
  }
}

/// Wraps a screen in the active theme [SkinScope] and renders the common shell.
class ShopScaffold extends ConsumerWidget {
  const ShopScaffold(
      {super.key,
      required this.title,
      required this.body,
      this.actions = const [],
      this.navIndex,
      this.bottom,
      this.titleWidget});
  final String title;
  final Widget? titleWidget;
  final Widget body;
  final List<Widget> actions;

  /// Highlights a bottom-nav tab (null => no bottom nav).
  final int? navIndex;
  final Widget? bottom;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final skin = ref.watch(resolvedThemeProvider).skin;
    return SkinScope(
      skin: skin,
      onOpen: openRoute,
      child: Scaffold(
        backgroundColor: skin.background,
        appBar: AppBar(
          backgroundColor: Colors.white,
          foregroundColor: skin.text,
          elevation: 0,
          scrolledUnderElevation: 1,
          centerTitle: skin.headerStyle == HeaderStyle.eloraV6,
          title: titleWidget ??
              Text(title,
                  style: TextStyle(color: skin.text, fontWeight: FontWeight.w800, fontSize: 18)),
          actions: actions,
        ),
        bottomNavigationBar: bottom ??
            (navIndex == null ? null : ConnectedBottomNav(current: navIndex!)),
        body: SafeArea(top: false, child: body),
      ),
    );
  }
}

/// Skeleton placeholder (shimmer) used instead of spinners.
class LoadingView extends StatelessWidget {
  const LoadingView({super.key});
  @override
  Widget build(BuildContext context) => Shimmer(
        key: const Key('loading'),
        child: SingleChildScrollView(
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(Space.gutter),
            child: Column(children: [
              for (var i = 0; i < 4; i++)
                const Padding(
                    padding: EdgeInsets.only(bottom: Space.x4),
                    child: Row(children: [
                      SkeletonBox(width: 72, height: 72, radius: 12),
                      SizedBox(width: Space.x3),
                      Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        SkeletonBox(height: 14),
                        SizedBox(height: Space.x2),
                        SkeletonBox(width: 120, height: 12),
                        SizedBox(height: Space.x2),
                        SkeletonBox(width: 60, height: 12),
                      ])),
                    ])),
            ])),
      );
}

class ErrorView extends StatelessWidget {
  const ErrorView(this.error, {super.key, this.onRetry});
  final Object error;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) {
    final offline = error is NetworkException;
    final s = SkinScope.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(offline ? Icons.wifi_off : Icons.error_outline, size: 48, color: s.subtitle),
          const SizedBox(height: 12),
          Text(
              offline
                  ? context.tr('No internet connection')
                  : (error is AppException
                      ? (error as AppException).message
                      : context.tr('Something went wrong')),
              key: Key(offline ? 'offline_view' : 'error_view'),
              textAlign: TextAlign.center,
              style: TextStyle(color: s.text)),
          if (onRetry != null) ...[
            const SizedBox(height: 12),
            SkinButton(
                key: const Key('retry'),
                label: context.tr('Retry'),
                onPressed: onRetry,
                expand: false),
          ],
        ]),
      ),
    );
  }
}

class EmptyView extends StatelessWidget {
  const EmptyView(this.message,
      {super.key, this.icon = Icons.inbox_outlined, this.actionLabel, this.onAction});
  final String message;
  final IconData icon;
  final String? actionLabel;
  final VoidCallback? onAction;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 56, color: s.subtitle),
          const SizedBox(height: 12),
          Text(message,
              key: const Key('empty_view'),
              textAlign: TextAlign.center,
              style: TextStyle(color: s.text, fontSize: 16)),
          if (actionLabel != null) ...[
            const SizedBox(height: 16),
            SkinButton(label: actionLabel!, onPressed: onAction, expand: false),
          ],
        ]),
      ),
    );
  }
}

/// AsyncValue renderer: loading / error(+retry) / data.
class AsyncBody<T> extends StatelessWidget {
  const AsyncBody(this.value, {super.key, required this.data, this.onRetry});
  final AsyncValue<T> value;
  final Widget Function(T) data;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) => value.when(
        data: data,
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(e, onRetry: onRetry),
        // never show the previous store's / account's data while reloading
        skipLoadingOnReload: false,
        skipLoadingOnRefresh: false,
      );
}

/// Themed primary / outline button (pill vs rounded per skin).
class SkinButton extends StatelessWidget {
  const SkinButton(
      {super.key,
      required this.label,
      required this.onPressed,
      this.outline = false,
      this.busy = false,
      this.expand = true,
      this.icon});
  final String label;
  final VoidCallback? onPressed;
  final bool outline, busy, expand;
  final IconData? icon;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final shape = RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(s.pillButtons ? 28 : s.radius));
    final child = busy
        ? const SizedBox(
            width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
        : Row(mainAxisSize: MainAxisSize.min, children: [
            if (icon != null) ...[Icon(icon, size: 18), const SizedBox(width: 6)],
            Flexible(child: Text(label, overflow: TextOverflow.ellipsis)),
          ]);
    final enabled = onPressed != null && !busy;
    final btn = outline
        ? OutlinedButton(
            onPressed: enabled ? onPressed : null,
            style: OutlinedButton.styleFrom(
                foregroundColor: s.primary,
                side: BorderSide(color: s.primary),
                shape: shape,
                minimumSize: const Size(0, 48)),
            child: child)
        : FilledButton(
            onPressed: enabled ? onPressed : null,
            style: FilledButton.styleFrom(
                backgroundColor: s.primary,
                foregroundColor: Colors.white,
                shape: shape,
                minimumSize: const Size(0, 48)),
            child: child);
    return expand ? SizedBox(width: double.infinity, child: btn) : btn;
  }
}

/// Price with the selected currency symbol.
class Price extends ConsumerWidget {
  const Price(this.amount, {super.key, this.style, this.symbol});
  final num? amount;
  final TextStyle? style;
  final String? symbol;
  @override
  Widget build(BuildContext context, WidgetRef ref) =>
      Text(formatPrice(symbol ?? ref.watch(currencySymbolProvider), amount),
          style: style);
}

String formatPrice(String symbol, num? v) {
  if (v == null) return '';
  final s = v.toStringAsFixed(2);
  return symbol.isEmpty ? s : '$symbol$s';
}

/// Heart toggle; signed-out users are sent to the login screen.
class FavoriteHeart extends ConsumerWidget {
  const FavoriteHeart(this.productId, {super.key, this.size = 20});
  final String productId;
  final double size;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SkinScope.of(context);
    final on = ref.watch(favoriteIdsProvider).value?.contains(productId) ?? false;
    return InkWell(
      key: Key('fav_$productId'),
      customBorder: const CircleBorder(),
      onTap: () async {
        if (!ref.read(isAuthenticatedProvider)) {
          openRoute(context, '/login?from=${Uri.encodeComponent('/favorites')}');
          return;
        }
        try {
          await ref.read(favoriteIdsProvider.notifier).toggle(productId);
        } on AppException catch (e) {
          if (context.mounted) {
            ScaffoldMessenger.of(context)
                .showSnackBar(SnackBar(content: Text(e.message)));
          }
        }
      },
      child: Container(
        width: size + 12,
        height: size + 12,
        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
        child: Icon(on ? Icons.favorite : Icons.favorite_border,
            size: size, color: on ? s.error : s.text),
      ),
    );
  }
}

/// Shows [child] only for signed-in customers, otherwise a sign-in prompt.
class RequireAuth extends ConsumerWidget {
  const RequireAuth({super.key, required this.child, this.from});
  final Widget child;
  final String? from;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authSessionProvider);
    return auth.when(
      loading: () => const LoadingView(),
      error: (e, _) => ErrorView(e, onRetry: () => ref.invalidate(authSessionProvider)),
      data: (ok) => ok
          ? child
          : EmptyView(context.tr('Sign in required'),
              icon: Icons.lock_outline,
              actionLabel: context.tr('Sign in'),
              onAction: () => context.push(
                  '/login${from == null ? '' : '?from=${Uri.encodeComponent(from!)}'}')),
    );
  }
}

/// Product card with a working favorite heart (all themes).
class ProductTile extends StatelessWidget {
  const ProductTile(this.product, {super.key, required this.width});
  final ProductItem product;
  final double width;
  @override
  Widget build(BuildContext context) => Stack(children: [
        ProductCard(product, width: width),
        PositionedDirectional(top: 4, end: 4, child: FavoriteHeart(product.id, size: 16)),
      ]);
}

void showError(BuildContext context, Object e) {
  final msg = e is AppException ? e.message : context.tr('Something went wrong');
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(msg)));
}

/// Bottom nav wired to cart count, favorites flag and `settings.bottom_nav`.
class ConnectedBottomNav extends ConsumerWidget {
  const ConnectedBottomNav({super.key, this.current = 0});
  final int current;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cfg = ref.watch(tenantControllerProvider.select((s) => s.config));
    return AppBottomNav(
      current: current,
      showFavorites: cfg?.feature('favorites', fallback: true) ?? true,
      cartCount: ref.watch(cartCountProvider),
      keys: cfg == null ? null : AppBottomNav.parseKeys(cfg.settings['bottom_nav']),
    );
  }
}
