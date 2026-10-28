import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../../design/design.dart';
import '../../themes/shared/chrome.dart';
import '../../themes/shared/skin.dart';
import '../tenant/tenant_models.dart';
import '../theme/theme_registry.dart';
import '../shop/commerce_models.dart' show CategoryNode;
import '../shop/commerce_providers.dart' show cartCountProvider, categoriesProvider;
import '../shop/widgets/shop_widgets.dart' show openRoute, ConnectedBottomNav;
import 'home_providers.dart';

/// Which bootstrap sections to render, in server order: only keys the active
/// theme has a builder for and (when the server sent a list) that are allowed
/// for the theme/variant. Unknown keys are ignored.
List<HomeSection> visibleSections(TenantConfig? config, ResolvedTheme theme) {
  if (config == null) return const [];
  final allowed = config.allowedSectionKeys[theme.definition.slug]?[theme.variantKey];
  return [
    for (final s in config.sections)
      if (theme.builderFor(s.key) != null && (allowed == null || allowed.contains(s.key))) s
  ];
}

/// Maps web-style links to in-app routes.
String? mapRoute(String route) {
  var path = route;
  final u = Uri.tryParse(route);
  if (u != null && u.hasScheme) path = u.path;
  if (!path.startsWith('/')) return null;
  path = path
      .replaceFirst(RegExp(r'^/products/'), '/product/')
      .replaceFirst(RegExp(r'^/categories/'), '/category/');
  return path;
}

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = ref.watch(tenantControllerProvider);
    final theme = ref.watch(resolvedThemeProvider);
    final config = s.config;
    final skin = theme.skin;
    final sections = visibleSections(config, theme);
    final name = config?.displayName ?? s.tenant?.name ?? 'Store';

    return SkinScope(
      skin: skin,
      onOpen: openRoute,
      child: Scaffold(
        backgroundColor: skin.background,
        bottomNavigationBar: const ConnectedBottomNav(current: 0),
        body: Column(children: [
          OfflineBanner(offline: s.fromCache),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.read(homeRepositoryProvider).clear();
                for (final sec in sections) {
                  ref.invalidate(homeSectionProvider(sec.key));
                }
              },
              child: CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                slivers: [
                  HomeSliverAppBar(
                      storeName: name,
                      logoUrl: config?.logoUrl,
                      cartCount: ref.watch(cartCountProvider),
                      showFavorites: config?.feature('favorites', fallback: true) ?? true),
                  SliverPersistentHeader(
                      pinned: true,
                      delegate: _ChipsHeader(
                          skin: skin,
                          cats: ref.watch(categoriesProvider).maybeWhen(
                              data: (c) => c, orElse: () => const []))),
                  SliverList.list(children: [
                    for (final sec in sections)
                      _SectionLoader(key: ValueKey('section_${sec.key}'), sectionKey: sec.key, theme: theme),
                    Center(
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Text(
                            '${theme.definition.slug}/${theme.variantKey}'
                            '${s.fromCache ? ' (offline cache)' : ''}',
                            key: const Key('home_marker'),
                            style: TextStyle(fontSize: 9, color: skin.subtitleAA)),
                      ),
                    ),
                  ]),
                ],
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

class _SectionLoader extends ConsumerWidget {
  const _SectionLoader({super.key, required this.sectionKey, required this.theme});
  final String sectionKey;
  final ResolvedTheme theme;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(homeSectionProvider(sectionKey));
    return async.when(
      data: (d) => FadeIn(child: theme.builderFor(sectionKey)!(context, {...d, 'key': sectionKey})),
      loading: () => const SectionSkeleton(),
      error: (_, _) => const SizedBox.shrink(), // a failing section never breaks home
    );
  }
}

/// Pinned category chips row under the app bar (collapses to nothing when
/// there are no categories).
class _ChipsHeader extends SliverPersistentHeaderDelegate {
  _ChipsHeader({required this.skin, required this.cats});
  final ThemeSkin skin;
  final List<CategoryNode> cats;
  @override
  double get minExtent => cats.isEmpty ? 0 : 52;
  @override
  double get maxExtent => minExtent;
  @override
  Widget build(BuildContext context, double shrink, bool overlaps) {
    if (cats.isEmpty) return const SizedBox.shrink();
    return Material(
      color: skin.surface,
      elevation: overlaps ? 2 : 0,
      child: ListView(
        key: const Key('pinned_chips'),
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: Space.gutter, vertical: Space.x2),
        children: [
          for (final c in cats)
            Padding(
              padding: const EdgeInsetsDirectional.only(end: Space.x2),
              child: ActionChip(
                  label: Text(c.name),
                  onPressed: () => openRoute(context, '/category/${c.slug}')),
            ),
        ],
      ),
    );
  }

  @override
  bool shouldRebuild(_ChipsHeader old) => old.cats != cats || old.skin != skin;
}
