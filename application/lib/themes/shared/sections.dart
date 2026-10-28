import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../features/home/home_providers.dart';
import 'models.dart';
import 'primitives.dart';
import 'product_card.dart';
import 'skin.dart';

const sectionTitles = <String, String>{
  'trending_now': 'Trending Now',
  'trending_this_week': 'Trending This Week',
  'best_seller': 'Best Seller',
  'best_selling': 'Best Selling',
  'new_arrivals': 'New Arrivals',
  'recommended_for_you': 'Recommended For You',
  'recommended_products': 'Recommended For You',
  'top_products': 'Top Products',
  'top_rated_products': 'Top Rated',
  'explore_products': 'Explore Products',
  'featured_products': 'Featured Products',
  'hot_deals': 'Hot Deals',
  'special_offers': 'Special Offers',
};

String _key(Map<String, dynamic> s) => '${s['key'] ?? ''}';

// ---------------------------------------------------------------- hero
class HeroBannerCarousel extends StatefulWidget {
  const HeroBannerCarousel(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  State<HeroBannerCarousel> createState() => _HeroState();
}

class _HeroState extends State<HeroBannerCarousel> {
  final _ctl = PageController();
  int _i = 0;
  Timer? _auto;

  @override
  void initState() {
    super.initState();
    final n = mapList(widget.section['banners']).length;
    if (n > 1) {
      _auto = Timer.periodic(const Duration(seconds: 5), (_) {
        if (!mounted || !_ctl.hasClients) return;
        _ctl.animateToPage((_i + 1) % n,
            duration: const Duration(milliseconds: 400), curve: Curves.easeInOut);
      });
    }
  }

  @override
  void dispose() {
    _auto?.cancel();
    _ctl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final banners = mapList(widget.section['banners']);
    if (banners.isEmpty) {
      if (s.themeSlug != 'ecommet') return const SizedBox.shrink();
      return Container(
          height: s.heroHeight,
          decoration: BoxDecoration(
              gradient: LinearGradient(colors: [s.primary, s.secondary])));
    }
    final overlay = s.themeSlug != 'ecommet';
    final dots = Row(mainAxisAlignment: MainAxisAlignment.center, children: [
      for (var d = 0; d < banners.length; d++)
        Container(
            width: d == _i ? 18 : 6,
            height: 6,
            margin: const EdgeInsets.symmetric(horizontal: 2),
            decoration: BoxDecoration(
                color: d == _i
                    ? s.primary
                    : (s.heroDotsBelow ? const Color(0xFFD4D4D4) : Colors.white70),
                borderRadius: BorderRadius.circular(3))),
    ]);
    final pager = SizedBox(
      height: s.heroHeight,
      child: Stack(children: [
        PageView.builder(
          key: const Key('hero_pager'),
          controller: _ctl,
          itemCount: banners.length,
          onPageChanged: (v) => setState(() => _i = v),
          itemBuilder: (_, i) {
            final b = banners[i];
            final title = b['title'] as String?;
            final sub = b['subtitle'] as String?;
            final btn = b['button_text'] as String?;
            return GestureDetector(
              onTap: () => SkinScope.open(context, b['link'] as String?),
              child: Stack(fit: StackFit.expand, children: [
                NetImage(b['image'] as String?),
                if (overlay && (title != null || sub != null))
                  DecoratedBox(
                      decoration: BoxDecoration(
                          gradient: LinearGradient(
                              begin: AlignmentDirectional.centerStart,
                              end: AlignmentDirectional.centerEnd,
                              colors: [
                        Colors.black.withValues(alpha: 0.45),
                        Colors.transparent
                      ]))),
                if (overlay && (title != null || sub != null))
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
                    child: Column(
                        mainAxisAlignment: s.variantKey == 'v1'
                            ? MainAxisAlignment.center
                            : MainAxisAlignment.end,
                        crossAxisAlignment: s.heroTextAlign,
                        children: [
                          if (title != null)
                            Text(title,
                                textAlign: s.heroTextAlign == CrossAxisAlignment.center
                                    ? TextAlign.center
                                    : TextAlign.start,
                                style: TextStyle(
                                    color: Colors.white,
                                    fontSize: s.variantKey == 'v5' ? 24 : 20,
                                    fontWeight: FontWeight.w700,
                                    height: 1.1)),
                          if (sub != null)
                            Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(sub,
                                    maxLines: 2,
                                    style: const TextStyle(color: Colors.white70, fontSize: 12))),
                          if (btn != null && btn.isNotEmpty)
                            Padding(
                                padding: const EdgeInsets.only(top: 10),
                                child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
                                    decoration: BoxDecoration(
                                        color: s.primary,
                                        borderRadius: BorderRadius.circular(s.pillButtons ? 20 : s.radius)),
                                    child: Text(btn,
                                        style: const TextStyle(
                                            color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)))),
                        ]),
                  ),
              ]),
            );
          },
        ),
        if (!s.heroDotsBelow && banners.length > 1)
          Positioned(left: 0, right: 0, bottom: 8, child: dots),
      ]),
    );
    return Column(children: [
      pager,
      if (s.heroDotsBelow && banners.length > 1)
        Padding(padding: const EdgeInsets.only(top: 12), child: dots),
    ]);
  }
}

class HeroBannersGrid extends StatelessWidget {
  const HeroBannersGrid(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final banners = mapList(section['banners']);
    if (banners.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Wrap(spacing: 8, runSpacing: 8, children: [
        for (final b in banners)
          GestureDetector(
            onTap: () => SkinScope.open(context, b['link'] as String?),
            child: ClipRRect(
                borderRadius: BorderRadius.circular(s.radius),
                child: SizedBox(
                    width: (MediaQuery.sizeOf(context).width - 40) / 2,
                    height: 110,
                    child: NetImage(b['image'] as String?))),
          ),
      ]),
    );
  }
}

// ---------------------------------------------------------------- trust bar
class TrustBar extends StatelessWidget {
  const TrustBar({super.key});
  static const items = [
    (Icons.lock_outline, 'Secure Privacy'),
    (Icons.credit_card, 'Safe payments'),
    (Icons.local_shipping_outlined, 'Delivery guarantee'),
  ];
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final ecommet = s.themeSlug == 'ecommet';
    final souq = s.themeSlug == 'souqify';
    // souqify v1: blue gradient + white text; v2..v6: amber #FFB00A + dark text.
    final souqBlue = souq && s.variantKey == 'v1';
    final fg = ecommet || souqBlue ? Colors.white : (souq ? Colors.black : s.text);
    final bg = ecommet
        ? s.success
        : (souq ? (souqBlue ? null : const Color(0xFFFFB00A)) : s.muted);
    return Container(
      margin: souq ? EdgeInsets.zero : const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
          color: bg,
          gradient: souqBlue
              ? const LinearGradient(
                  colors: [Color(0xFF002562), Color(0xFF144AA6), Color(0xFF003387)])
              : null,
          borderRadius: souq ? null : BorderRadius.circular(s.radius),
          border: ecommet ? Border.all(color: s.success, width: 2) : null),
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
        for (final it in items)
          Flexible(
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(it.$1, size: 16, color: ecommet || souq ? fg : s.primary),
              const SizedBox(width: 4),
              Flexible(
                  child: Text(it.$2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w500))),
            ]),
          ),
      ]),
    );
  }
}

// ---------------------------------------------------------------- categories
class CategoryCircles extends StatelessWidget {
  const CategoryCircles(this.section, {super.key, this.rows = 1, this.maxItems = 20});
  final Map<String, dynamic> section;
  final int rows, maxItems;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final cats = categoriesOf(section['categories']).take(maxItems).toList();
    if (cats.isEmpty) return const SizedBox.shrink();
    Widget item(CategoryItem c) => GestureDetector(
          onTap: () => SkinScope.open(context, categoryRoute(c)),
          child: SizedBox(
            width: 72,
            child: Column(children: [
              ClipOval(child: SizedBox(width: 60, height: 60, child: NetImage(c.image))),
              const SizedBox(height: 6),
              Text(c.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: s.text, fontSize: 11, fontWeight: FontWeight.w500)),
            ]),
          ),
        );
    if (rows <= 1) {
      return SizedBox(
        height: 96,
        child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: cats.length,
            separatorBuilder: (_, _) => const SizedBox(width: 8),
            itemBuilder: (_, i) => item(cats[i])),
      );
    }
    final half = (cats.length / 2).ceil();
    return Column(children: [
      for (final chunk in [cats.take(half).toList(), cats.skip(half).toList()])
        if (chunk.isNotEmpty)
          Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: SizedBox(
                height: 96,
                child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: chunk.length,
                    separatorBuilder: (_, _) => const SizedBox(width: 8),
                    itemBuilder: (_, i) => item(chunk[i])),
              )),
    ]);
  }
}

/// Category pill row (ecommet `_categories-slider`).
class CategoryPills extends StatelessWidget {
  const CategoryPills(this.cats, {super.key});
  final List<CategoryItem> cats;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return SizedBox(
      height: 40,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: [
          for (var i = -1; i < cats.length; i++)
            Padding(
              padding: const EdgeInsetsDirectional.only(end: 8),
              child: GestureDetector(
                onTap: i < 0 ? null : () => SkinScope.open(context, categoryRoute(cats[i])),
                child: Container(
                  alignment: Alignment.center,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                      color: i < 0 ? s.text : Colors.white,
                      border: Border.all(color: s.border),
                      borderRadius: BorderRadius.circular(20)),
                  child: Text(i < 0 ? 'All' : cats[i].name,
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: i < 0 ? Colors.white : s.text)),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------- products
/// Product grid with a "See More" button that asks the API for more items.
class LoadMoreProducts extends ConsumerStatefulWidget {
  const LoadMoreProducts(this.sectionKey, this.initial,
      {super.key, this.step = 10, this.grid = true});
  final String sectionKey;
  final List<ProductItem> initial;
  final int step;
  final bool grid;
  @override
  ConsumerState<LoadMoreProducts> createState() => _LoadMoreState();
}

class _LoadMoreState extends ConsumerState<LoadMoreProducts> {
  late List<ProductItem> _items = widget.initial;
  late int _limit = widget.initial.length;
  bool _loading = false, _done = false;

  Future<void> _more() async {
    setState(() => _loading = true);
    try {
      final next = _limit + widget.step;
      final d = await ref
          .read(homeRepositoryProvider)
          .section(widget.sectionKey, limit: next);
      final list = productsOf(d['products']);
      setState(() {
        _done = list.length <= _items.length;
        _items = list;
        _limit = next;
      });
    } catch (_) {
      // keep what we have
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_items.isEmpty) return const SizedBox.shrink();
    return Column(children: [
      widget.grid ? ProductGrid(_items) : ProductRow(_items),
      if (!_done && _items.length >= widget.step) ...[
        const SizedBox(height: 20),
        LoadMore(onPressed: _more, loading: _loading),
      ],
    ]);
  }
}

class ProductRail extends StatelessWidget {
  const ProductRail(this.section, {super.key, this.title, this.grid = false});
  final Map<String, dynamic> section;
  final String? title;
  final bool grid;
  @override
  Widget build(BuildContext context) {
    final products = productsOf(section['products']);
    if (products.isEmpty) return const SizedBox.shrink();
    final k = _key(section);
    final t = title ?? sectionTitles[k] ?? k;
    final see = section['see_all'] as String?;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SectionTitle(t,
          center: grid,
          actionLabel: see != null ? 'See all' : null,
          onAction: see == null
              ? null
              : () => SkinScope.open(context, see.startsWith('/') ? see : '/category/$see')),
      grid ? LoadMoreProducts(k, products) : ProductRow(products),
    ]);
  }
}

class CategoryPillsProductGrid extends StatelessWidget {
  const CategoryPillsProductGrid(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final cats = categoriesOf(section['categories']);
    final products = productsOf(section['products']);
    return Column(children: [
      Text('New Year Sale',
          style: TextStyle(color: s.primary, fontWeight: FontWeight.w700, fontSize: 20)),
      const SizedBox(height: 4),
      Text('EXPLORE YOUR INTERESTS',
          style: TextStyle(
              color: s.text, fontWeight: FontWeight.w900, fontSize: 18, letterSpacing: 2)),
      const SizedBox(height: 14),
      CategoryPills(cats),
      const SizedBox(height: 14),
      if (products.isEmpty)
        Padding(
            padding: const EdgeInsets.all(24),
            child: Text('No new products available yet.', style: TextStyle(color: s.subtitle)))
      else
        LoadMoreProducts(_key(section), products),
    ]);
  }
}

// ---------------------------------------------------------------- flash
class FlashSaleStrip extends StatelessWidget {
  const FlashSaleStrip(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final flash = mapOf(section['flash']);
    if (mapList(flash['sales']).isEmpty) return const SizedBox.shrink();
    final ecommet = s.themeSlug == 'ecommet';
    final coupon = flash['coupon_code'] as String?;
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
          color: ecommet ? const Color(0xFFFEF2F2) : s.primary.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(ecommet ? 0 : s.radius + 4),
          border: Border.symmetric(
              horizontal: BorderSide(color: s.primary.withValues(alpha: 0.2)))),
      child: Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Flash Sale now on!',
                style: TextStyle(
                    color: s.text, fontWeight: FontWeight.w600, fontSize: 13)),
            const SizedBox(height: 4),
            Countdown(flash['ends_at'] as String?,
                style: TextStyle(
                    color: s.primary,
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                    letterSpacing: 2)),
            if (coupon != null)
              Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text('Code: $coupon',
                      style: TextStyle(color: s.subtitle, fontSize: 11))),
          ]),
        ),
        GestureDetector(
          onTap: () => SkinScope.open(context, '/category/flash-sale'),
          child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                  color: ecommet ? const Color(0xFF232323) : s.primary,
                  borderRadius: BorderRadius.circular(20)),
              child: const Text('Shop flash deals',
                  style: TextStyle(
                      color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700))),
        ),
      ]),
    );
  }
}

class FlashDealsRail extends StatelessWidget {
  const FlashDealsRail(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final flash = mapOf(section['flash']);
    final products = productsOf(flash['products']);
    if (products.isEmpty) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        child: Row(children: [
          Icon(Icons.bolt, color: s.primary),
          const SizedBox(width: 4),
          Text('FLASH DEALS',
              style: TextStyle(
                  color: s.primary, fontWeight: FontWeight.w900, fontSize: 15, letterSpacing: 0.5)),
          const Spacer(),
          Countdown(flash['ends_at'] as String?,
              style: TextStyle(color: s.text, fontSize: 11, fontWeight: FontWeight.w700)),
        ]),
      ),
      ProductRow(products, badge: null),
    ]);
  }
}

// ---------------------------------------------------------------- tabbed
class TabbedProducts extends ConsumerStatefulWidget {
  const TabbedProducts(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  ConsumerState<TabbedProducts> createState() => _TabbedState();
}

const tabLabels = {
  'cat-flash': 'Flash Sale',
  'cat-bestselling': 'Best Selling',
  'cat-newin': 'New In',
  'cat-toprated': 'Top Rated',
};

class _TabbedState extends ConsumerState<TabbedProducts> {
  late String _tab = '${widget.section['default_tab'] ?? 'cat-flash'}';
  List<ProductItem> _items = const [];
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _items = productsOf(widget.section['products']);
    if (_items.isEmpty) _load(_tab);
  }

  Future<void> _load(String tab) async {
    setState(() {
      _tab = tab;
      _loading = true;
    });
    try {
      final d = await ref.read(homeRepositoryProvider).section(
          _key(widget.section),
          tab: tab);
      if (mounted && _tab == tab) setState(() => _items = productsOf(d['products']));
    } catch (_) {
      if (mounted) setState(() => _items = const []);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final tabs = [
      for (final t in (widget.section['tabs'] as List? ?? const [])) '$t'
    ];
    if (tabs.isEmpty) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SizedBox(
        height: 40,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          children: [
            for (final t in tabs)
              GestureDetector(
                key: Key('tab_$t'),
                onTap: () => _load(t),
                child: Container(
                  margin: const EdgeInsetsDirectional.only(end: 20),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                      border: Border(
                          bottom: BorderSide(
                              color: t == _tab ? s.primary : Colors.transparent, width: 3))),
                  child: Text(tabLabels[t] ?? t,
                      style: TextStyle(
                          color: t == _tab ? s.primary : s.subtitle,
                          fontWeight: FontWeight.w700,
                          fontSize: 14)),
                ),
              ),
          ],
        ),
      ),
      const SizedBox(height: 12),
      if (_loading)
        const Padding(
            padding: EdgeInsets.all(24),
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)))
      else if (_items.isNotEmpty)
        ProductRow(_items),
    ]);
  }
}

// ---------------------------------------------------------------- misc
class ShopByCategoryBuyTogether extends StatelessWidget {
  const ShopByCategoryBuyTogether(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final cats = categoriesOf(section['categories']);
    final pair = productsOf(section['products']);
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const SectionTitle('Shop by Category'),
      if (cats.isNotEmpty)
        SizedBox(
          height: 120,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: cats.length,
            separatorBuilder: (_, _) => const SizedBox(width: 10),
            itemBuilder: (_, i) => GestureDetector(
              onTap: () => SkinScope.open(context, categoryRoute(cats[i])),
              child: SizedBox(
                width: 96,
                child: Column(children: [
                  Expanded(
                      child: ClipRRect(
                          borderRadius: BorderRadius.circular(s.radius),
                          child: SizedBox(width: 96, child: NetImage(cats[i].image)))),
                  const SizedBox(height: 4),
                  Text(cats[i].name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(color: s.text, fontSize: 12, fontWeight: FontWeight.w600)),
                ]),
              ),
            ),
          ),
        ),
      if (pair.length >= 2) ...[
        const SizedBox(height: 20),
        const SectionTitle('Buy Together'),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(children: [
            for (var i = 0; i < 2; i++) ...[
              if (i == 1)
                Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 6),
                    child: Icon(Icons.add, color: s.primary)),
              Expanded(
                  child: LayoutBuilder(
                      builder: (_, c) => ProductCard(pair[i], width: c.maxWidth))),
            ]
          ]),
        ),
      ],
    ]);
  }
}

class PromoBanner extends StatelessWidget {
  const PromoBanner(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final p = section['promo'];
    if (p is! Map) return const SizedBox.shrink();
    final img = (p['mobile_image'] ?? p['image']) as String?;
    final title = p['title'] as String?;
    final sub = p['subtitle'] as String?;
    final cta = (p['cta_text'] as String?)?.isNotEmpty == true ? p['cta_text'] as String : 'Shop Now';
    return GestureDetector(
      onTap: () => SkinScope.open(context, p['link'] as String?),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 16),
        height: 200,
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
            color: const Color(0xFF111827),
            borderRadius: BorderRadius.circular(s.themeSlug == 'ecommet' ? 16 : s.radius + 4)),
        child: Stack(fit: StackFit.expand, children: [
          if (img != null) Opacity(opacity: 0.75, child: NetImage(img)),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (title != null)
                    Text(title,
                        style: const TextStyle(
                            color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
                  if (sub != null)
                    Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(sub,
                            style: const TextStyle(color: Colors.white70, fontSize: 13))),
                  const SizedBox(height: 14),
                  Container(
                      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 9),
                      decoration: BoxDecoration(
                          color: Colors.white, borderRadius: BorderRadius.circular(20)),
                      child: Text(cta,
                          style: const TextStyle(
                              color: Color(0xFF111827),
                              fontWeight: FontWeight.w700,
                              fontSize: 13))),
                ]),
          ),
        ]),
      ),
    );
  }
}

class SpecialOffersTeaser extends StatelessWidget {
  const SpecialOffersTeaser(this.section, {super.key});
  final Map<String, dynamic> section;
  @override
  Widget build(BuildContext context) => ProductRail(section, title: 'Special Offers');
}
