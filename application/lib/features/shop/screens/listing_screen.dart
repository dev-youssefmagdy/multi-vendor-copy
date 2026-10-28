import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/providers.dart';
import '../../../core/l10n/strings.dart';
import '../../../design/design.dart';
import '../../../themes/shared/primitives.dart';
import '../../../themes/shared/models.dart';
import '../../../themes/shared/skin.dart';
import '../commerce_models.dart';
import '../commerce_providers.dart';
import '../widgets/shop_widgets.dart';

/// Maps `/category/:slug` and `/list/:name` onto a listing query. Home
/// "see all" links use `/category/new-in` etc., so the special names are
/// resolved here (S17-S20).
ListingQuery queryForSlug(String slug) {
  switch (slug) {
    case 'best-selling' || 'best_selling' || 'top_products':
      return const ListingQuery(ListingKind.list, slug: 'best-selling');
    case 'new-in' || 'new_in' || 'new_arrivals':
      return const ListingQuery(ListingKind.list, slug: 'new-in');
    case 'full-star' || 'top-rated' || 'top_rated':
      return const ListingQuery(ListingKind.list, slug: 'top-rated');
    case 'offers' || 'flash_sale' || 'hot_deals' || 'special_offers':
      return const ListingQuery(ListingKind.offers);
    case 'all' || 'all-products':
      return const ListingQuery(ListingKind.all);
  }
  return ListingQuery(ListingKind.category, slug: slug);
}

String titleForQuery(BuildContext c, ListingQuery q) {
  if (q.kind == ListingKind.offers) return c.tr('Offers');
  if (q.kind == ListingKind.search) return c.tr('Search');
  if (q.kind == ListingKind.list) {
    return switch (q.slug) {
      'best-selling' => c.tr('Best selling'),
      'new-in' => c.tr('New in'),
      _ => c.tr('Top rated'),
    };
  }
  if (q.kind == ListingKind.all) return c.tr('Categories');
  final words = q.slug.replaceAll(RegExp(r'[-_]+'), ' ').trim();
  return words.isEmpty ? '' : words[0].toUpperCase() + words.substring(1);
}

const sortOptions = {
  'latest': 'Newest',
  'ascending': 'Price: low to high',
  'descending': 'Price: high to low',
  'rating': 'Top rated',
  'best_selling': 'Best selling',
};

class ListingScreen extends ConsumerStatefulWidget {
  const ListingScreen(this.initial, {super.key, this.title, this.navIndex});
  final ListingQuery initial;
  final String? title;
  final int? navIndex;
  @override
  ConsumerState<ListingScreen> createState() => _ListingScreenState();
}

class _ListingScreenState extends ConsumerState<ListingScreen> {
  late ListingQuery _q = widget.initial;
  final _scroll = ScrollController();
  bool _list = false;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.pixels > _scroll.position.maxScrollExtent - 400) {
        ref.read(listingProvider(_q).notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _openFilters() async {
    final skin = ref.read(resolvedThemeProvider).skin;
    final r = await showModalBottomSheet<ListingQuery>(
      context: context,
      isScrollControlled: true,
      builder: (_) => SkinScope(skin: skin, child: _FilterSheet(_q)),
    );
    if (r != null) setState(() => _q = r);
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(listingProvider(_q));
    final notifier = ref.read(listingProvider(_q).notifier);
    return ShopScaffold(
      title: widget.title ?? titleForQuery(context, _q),
      navIndex: widget.navIndex,
      actions: [
        IconButton(
          key: const Key('toggle_layout'),
          tooltip: context.tr(_list ? 'Grid view' : 'List view'),
          onPressed: () {
            Haptics.tap();
            setState(() => _list = !_list);
          },
          icon: Icon(_list ? Icons.grid_view : Icons.view_agenda_outlined),
        ),
        IconButton(
          key: const Key('open_filters'),
          tooltip: context.tr('Filters'),
          onPressed: _openFilters,
          icon: Badge(
            isLabelVisible: _q.hasFilters || _q.sort != 'latest',
            smallSize: 8,
            child: const Icon(Icons.tune),
          ),
        ),
      ],
      body: Builder(
        builder: (context) {
          if (state.initialLoading) return const LoadingView();
          if (state.error != null && state.items.isEmpty) {
            return ErrorView(state.error!, onRetry: notifier.retry);
          }
          if (state.items.isEmpty) {
            return EmptyView(context.tr('No results'), icon: Icons.search_off);
          }
          return Column(
            children: [
              if (_q.hasFilters || _q.sort != 'latest')
                _ActiveFilters(_q, (q) => setState(() => _q = q)),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: notifier.refresh,
                  child: ProductGridView(
                    key: const Key('listing_grid'),
                    products: state.items,
                    controller: _scroll,
                    list: _list,
                    footer: state.loading
                        ? const Padding(
                            padding: EdgeInsets.all(Space.x4),
                            child: SectionSkeleton(cards: 2, height: 100),
                          )
                        : (state.error != null
                              ? ErrorView(state.error!, onRetry: notifier.retry)
                              : null),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

/// Removable chips for the active sort/filters.
class _ActiveFilters extends StatelessWidget {
  const _ActiveFilters(this.q, this.onChange);
  final ListingQuery q;
  final void Function(ListingQuery) onChange;
  @override
  Widget build(BuildContext context) {
    final chips = <Widget>[
      if (q.sort != 'latest')
        InputChip(
          label: Text(context.tr(sortOptions[q.sort] ?? q.sort)),
          onDeleted: () => onChange(q.copyWith(sort: 'latest')),
        ),
      if (q.min != null || q.max != null)
        InputChip(
          label: Text(
            '${q.min != null ? money(q.min) : '0'} - ${q.max != null ? money(q.max) : '∞'}',
          ),
          onDeleted: () => onChange(q.copyWith(clearPrice: true)),
        ),
      if (q.availability.isNotEmpty)
        InputChip(
          label: Text(context.tr('In stock')),
          onDeleted: () => onChange(q.copyWith(availability: '')),
        ),
      if (q.ratings != null)
        InputChip(
          label: Text('${q.ratings}★ +'),
          onDeleted: () => onChange(q.copyWith(clearRatings: true)),
        ),
    ];
    return SizedBox(
      height: kMinTouch,
      child: ListView(
        key: const Key('active_filters'),
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(
          horizontal: Space.gutter,
          vertical: Space.x1,
        ),
        children: [
          for (final c in chips)
            Padding(
              padding: const EdgeInsetsDirectional.only(end: Space.x2),
              child: c,
            ),
        ],
      ),
    );
  }
}

/// Lazy responsive grid (2 cols on phones, more on tablets) or a list.
class ProductGridView extends StatelessWidget {
  const ProductGridView({
    super.key,
    required this.products,
    this.controller,
    this.footer,
    this.list = false,
  });
  final List<dynamic> products;
  final ScrollController? controller;
  final Widget? footer;
  final bool list;

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return LayoutBuilder(
      builder: (context, c) {
        const gap = 10.0;
        final cols = Breakpoints.gridColumns(c.maxWidth);
        final w = (c.maxWidth - 32 - gap * (cols - 1)) / cols;
        final imgH = s.cardStyle == CardStyle.square ? w : s.cardImageHeight;
        final scale = MediaQuery.textScalerOf(context).scale(1.0);
        final extra = 118 * scale.clamp(1.0, 2.0);
        return CustomScrollView(
          controller: controller,
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.all(16),
              sliver: list
                  ? SliverList.separated(
                      itemCount: products.length,
                      separatorBuilder: (_, _) => const SizedBox(height: gap),
                      itemBuilder: (_, i) => ProductRowTile(products[i]),
                    )
                  : SliverGrid(
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: cols,
                        crossAxisSpacing: gap,
                        mainAxisSpacing: gap,
                        mainAxisExtent: imgH + extra,
                      ),
                      delegate: SliverChildBuilderDelegate(
                        (_, i) => ProductTile(products[i], width: w),
                        childCount: products.length,
                      ),
                    ),
            ),
            if (footer != null) SliverToBoxAdapter(child: footer),
          ],
        );
      },
    );
  }
}

/// Horizontal list-mode row for a product.
class ProductRowTile extends StatelessWidget {
  const ProductRowTile(this.p, {super.key});
  final ProductItem p;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Semantics(
      button: true,
      label: p.name,
      child: InkWell(
        borderRadius: BorderRadius.circular(s.cardRadius),
        onTap: () => SkinScope.open(context, '/product/${p.slug}'),
        child: Container(
          constraints: const BoxConstraints(minHeight: 104),
          decoration: BoxDecoration(
            border: Border.all(color: s.border),
            borderRadius: BorderRadius.circular(s.cardRadius),
          ),
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                ClipRRect(
                  borderRadius: BorderRadiusDirectional.horizontal(
                    start: Radius.circular(s.cardRadius),
                  ).resolve(Directionality.of(context)),
                  child: SizedBox(width: 104, child: NetImage(p.image)),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(Space.x3),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          p.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: s.text,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: Space.x1),
                        if (p.rating != null) RatingStars(p.rating!, size: 14),
                        const SizedBox(height: Space.x1),
                        Text(
                          money(p.price),
                          style: TextStyle(
                            color: s.primaryAA,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                FavoriteHeart(p.id, size: 18),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _FilterSheet extends StatefulWidget {
  const _FilterSheet(this.q);
  final ListingQuery q;
  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  late String sort = widget.q.sort;
  late String availability = widget.q.availability;
  int? ratings;
  late final TextEditingController min = TextEditingController(
    text: widget.q.min?.toString() ?? '',
  );
  late final TextEditingController max = TextEditingController(
    text: widget.q.max?.toString() ?? '',
  );

  @override
  void initState() {
    super.initState();
    ratings = widget.q.ratings;
  }

  @override
  void dispose() {
    min.dispose();
    max.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              context.tr('Sort'),
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
            Wrap(
              spacing: 8,
              children: [
                for (final e in sortOptions.entries)
                  ChoiceChip(
                    key: Key('sort_${e.key}'),
                    label: Text(context.tr(e.value)),
                    selected: sort == e.key,
                    onSelected: (_) => setState(() => sort = e.key),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    key: const Key('filter_min'),
                    controller: min,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: context.tr('Min price'),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextField(
                    key: const Key('filter_max'),
                    controller: max,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: context.tr('Max price'),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              children: [
                FilterChip(
                  label: Text(context.tr('In stock')),
                  selected: availability == 'in_stock',
                  onSelected: (v) =>
                      setState(() => availability = v ? 'in_stock' : ''),
                ),
                for (final r in [4, 3])
                  FilterChip(
                    label: Text('$r★ +'),
                    selected: ratings == r,
                    onSelected: (v) => setState(() => ratings = v ? r : null),
                  ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: SkinButton(
                    outline: true,
                    label: context.tr('Reset'),
                    onPressed: () => Navigator.pop(
                      context,
                      widget.q.copyWith(
                        sort: 'latest',
                        availability: '',
                        clearPrice: true,
                        clearRatings: true,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: SkinButton(
                    key: const Key('apply_filters'),
                    label: context.tr('Apply'),
                    onPressed: () => Navigator.pop(
                      context,
                      ListingQuery(
                        widget.q.kind,
                        slug: widget.q.slug,
                        keyword: widget.q.keyword,
                        sort: sort,
                        availability: availability,
                        ratings: ratings,
                        min: num.tryParse(min.text),
                        max: num.tryParse(max.text),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
