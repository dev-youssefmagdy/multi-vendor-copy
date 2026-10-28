import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_widget_from_html_core/flutter_widget_from_html_core.dart';
import 'package:go_router/go_router.dart';
import '../../../core/l10n/strings.dart';
import '../../../core/platform/html_text.dart';
import '../../../themes/shared/primitives.dart';
import '../../../themes/shared/skin.dart';
import '../commerce_models.dart';
import '../commerce_providers.dart';
import '../widgets/shop_widgets.dart';

/// Category tree (bottom-nav "Categories" tab, `/category/all`).
class CategoriesScreen extends ConsumerWidget {
  const CategoriesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(categoriesProvider);
    return ShopScaffold(
      title: context.tr('Categories'),
      navIndex: 1,
      body: AsyncBody<List<CategoryNode>>(async,
          onRetry: () => ref.invalidate(categoriesProvider),
          data: (cats) => cats.isEmpty
              ? EmptyView(context.tr('No categories'))
              : ListView(children: [
                  ListTile(
                      key: const Key('all_products'),
                      leading: const Icon(Icons.apps),
                      title: Text(context.tr('All products')),
                      onTap: () => openRoute(context, '/category/all-products')),
                  for (final c in cats) _CategoryTile(c),
                ])),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile(this.c);
  final CategoryNode c;
  @override
  Widget build(BuildContext context) {
    final lead = SizedBox(
        width: 44,
        height: 44,
        child: ClipRRect(
            borderRadius: BorderRadius.circular(8), child: NetImage(c.image)));
    if (c.children.isEmpty) {
      return ListTile(
          key: Key('cat_${c.slug}'),
          leading: lead,
          title: Text(c.name),
          onTap: () => openRoute(context, '/category/${c.slug}'));
    }
    return ExpansionTile(
      key: Key('cat_${c.slug}'),
      leading: lead,
      title: Text(c.name),
      children: [
        ListTile(
            title: Text(context.tr('All :name', {'name': c.name})),
            onTap: () => openRoute(context, '/category/${c.slug}')),
        for (final ch in c.children)
          Padding(
              padding: const EdgeInsetsDirectional.only(start: 16), child: _CategoryTile(ch)),
      ],
    );
  }
}

/// S21: CMS page (title + body).
class CmsPageScreen extends ConsumerWidget {
  const CmsPageScreen(this.slug, {super.key});
  final String slug;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(cmsPageProvider(slug));
    final notFound = async.error is Object &&
        async.hasError &&
        (async.error.toString().contains('404') || async.error.toString().toLowerCase().contains('not found'));
    return ShopScaffold(
      title: async.value?.title ?? '',
      body: notFound
          ? const NotFoundView()
          : AsyncBody<CmsPage>(async,
              onRetry: () => ref.invalidate(cmsPageProvider(slug)),
              data: (p) => ListView(padding: const EdgeInsets.all(16), children: [
                    Text(p.title ?? '',
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 12),
                    if (p.bodyHtml != null && p.bodyHtml!.isNotEmpty)
                      HtmlWidget(p.bodyHtml!, key: const Key('cms_body'))
                    else
                      Text(htmlToText(p.body),
                          key: const Key('cms_body'),
                          style: const TextStyle(height: 1.6)),
                  ])),
    );
  }
}

/// S22: 404.
class NotFoundView extends StatelessWidget {
  const NotFoundView({super.key});
  @override
  Widget build(BuildContext context) => EmptyView(context.tr('Page not found'),
      icon: Icons.sentiment_dissatisfied_outlined,
      actionLabel: context.tr('Go home'),
      onAction: () => GoRouter.of(context).go('/home'));
}

class NotFoundScreen extends ConsumerWidget {
  const NotFoundScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) =>
      ShopScaffold(title: '404', body: const NotFoundView());
}

/// Links to CMS pages (privacy, terms, ...) listed from `/menus`.
class PagesListSection extends ConsumerWidget {
  const PagesListSection({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SkinScope.of(context);
    return ref.watch(menuPagesProvider).maybeWhen(
          data: (pages) => Column(children: [
            for (final p in pages)
              ListTile(
                  key: Key('page_${p['slug']}'),
                  leading: Icon(Icons.description_outlined, color: s.subtitle),
                  title: Text('${p['title']}'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => openRoute(context, '/page/${p['slug']}')),
          ]),
          orElse: () => const SizedBox.shrink(),
        );
  }
}
