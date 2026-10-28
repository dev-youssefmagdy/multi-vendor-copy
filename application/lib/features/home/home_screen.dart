import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../app/providers.dart';
import '../../themes/shared/chrome.dart';
import '../../themes/shared/skin.dart';
import '../tenant/tenant_models.dart';
import '../theme/theme_registry.dart';
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
      onOpen: (ctx, route) {
        final r = mapRoute(route);
        final router = GoRouter.maybeOf(ctx);
        if (r != null && router != null) router.push(r);
      },
      child: Scaffold(
        backgroundColor: skin.background,
        bottomNavigationBar: AppBottomNav(showFavorites: config?.feature('favorites', fallback: true) ?? true),
        body: Column(children: [
          HeaderBar(
              storeName: name,
              logoUrl: config?.logoUrl,
              showFavorites: config?.feature('favorites', fallback: true) ?? true),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.read(homeRepositoryProvider).clear();
                for (final sec in sections) {
                  ref.invalidate(homeSectionProvider(sec.key));
                }
              },
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  for (final sec in sections)
                    _SectionLoader(key: ValueKey('section_${sec.key}'), sectionKey: sec.key, theme: theme),
                  const SizedBox(height: 8),
                  FooterInfo(
                    text: config?.footer['text'] as String?,
                    copyright: config?.footer['copyright'] as String?,
                    social: config?.socialLinks ?? const [],
                  ),
                  Center(
                    child: Padding(
                      padding: const EdgeInsets.all(6),
                      child: Text(
                          '${theme.definition.slug}/${theme.variantKey}'
                          '${s.fromCache ? ' (offline cache)' : ''}',
                          key: const Key('home_marker'),
                          style: TextStyle(fontSize: 9, color: skin.subtitle)),
                    ),
                  ),
                ]),
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
      data: (d) => theme.builderFor(sectionKey)!(context, {...d, 'key': sectionKey}),
      loading: () => const SizedBox(height: 80),
      error: (_, _) => const SizedBox.shrink(), // a failing section never breaks home
    );
  }
}
