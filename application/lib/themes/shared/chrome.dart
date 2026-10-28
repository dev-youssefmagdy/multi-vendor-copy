import 'package:flutter/material.dart';
import '../../core/l10n/strings.dart';
import '../../design/design.dart';
import 'primitives.dart';
import 'skin.dart';

/// Header shell: logo / store name, search field, favorites + cart.
/// Skin controls search shape, divider and whether the search sits inline.
class HeaderBar extends StatelessWidget {
  const HeaderBar({super.key, required this.storeName, this.logoUrl, this.showFavorites = true});
  final String storeName;
  final String? logoUrl;
  final bool showFavorites;

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final st = s.headerStyle;
    final searchRadius = s.searchRadius;
    final filled = s.searchFilled;
    final logo = logoUrl != null && logoUrl!.isNotEmpty
        ? SizedBox(height: 32, child: NetImage(logoUrl, fit: BoxFit.contain))
        : Text(storeName,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
                color: s.text, fontWeight: FontWeight.w900, fontSize: 20, letterSpacing: -0.3));
    final icons = [
      if (showFavorites)
        IconButton(
            key: const Key('header_favorites'),
            onPressed: () => SkinScope.open(context, '/favorites'),
            icon: Icon(Icons.favorite_border, color: s.text)),
      IconButton(
          key: const Key('header_cart'),
          onPressed: () => SkinScope.open(context, '/cart'),
          icon: Icon(Icons.shopping_bag_outlined, color: s.text)),
    ];
    final search = GestureDetector(
      key: const Key('header_search'),
      onTap: () => SkinScope.open(context, '/search'),
      child: Container(
        height: 42,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
            color: filled ? s.muted : Colors.white,
            border: filled ? null : Border.all(color: s.border),
            borderRadius: BorderRadius.circular(searchRadius)),
        child: Row(children: [
          Icon(Icons.search, size: 20, color: s.subtitle),
          const SizedBox(width: 8),
          Text('Search products', style: TextStyle(color: s.subtitle, fontSize: 14)),
        ]),
      ),
    );
    return Material(
      color: Colors.white,
      elevation: st == HeaderStyle.eloraV3 || st == HeaderStyle.eloraV6 ? 1 : 0,
      child: SafeArea(
        bottom: false,
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 6, 8, 10),
          decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: s.border.withValues(alpha: 0.7)))),
          child: Column(children: [
            Row(children: [Expanded(child: Align(alignment: AlignmentDirectional.centerStart, child: logo)), ...icons]),
            Padding(padding: const EdgeInsetsDirectional.only(end: 8), child: search),
          ]),
        ),
      ),
    );
  }
}

/// Footer shell: about text, social links, copyright.
class FooterInfo extends StatelessWidget {
  const FooterInfo(
      {super.key, this.text, this.copyright, this.social = const [], this.pages = const []});
  final String? text, copyright;
  final List<Map<String, dynamic>> social;
  final List<Map<String, dynamic>> pages;

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    if (!s.showFooter) return const SizedBox.shrink();
    final bg = s.footerBg;
    const fg = Colors.white;
    const sub = Colors.white70;
    return Container(
      width: double.infinity,
      color: bg,
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (text != null && text!.isNotEmpty)
          Text(text!, style: TextStyle(color: fg, fontSize: 13, height: 1.5)),
        if (pages.isNotEmpty) ...[
          const SizedBox(height: 14),
          Wrap(spacing: 16, runSpacing: 6, children: [
            for (final p in pages)
              GestureDetector(
                  onTap: () => SkinScope.open(context, '/page/${p['slug']}'),
                  child: Text('${p['title'] ?? p['slug']}',
                      style: TextStyle(color: fg, fontSize: 13, fontWeight: FontWeight.w600))),
          ]),
        ],
        if (social.isNotEmpty) ...[
          const SizedBox(height: 14),
          Wrap(spacing: 12, children: [
            for (final l in social)
              Chip(
                  label: Text('${l['icon'] ?? 'link'}', style: TextStyle(color: fg, fontSize: 11)),
                  backgroundColor: Colors.transparent,
                  side: BorderSide(color: sub)),
          ]),
        ],
        if (copyright != null && copyright!.isNotEmpty) ...[
          const SizedBox(height: 14),
          Text(copyright!, style: TextStyle(color: sub, fontSize: 11)),
        ],
      ]),
    );
  }
}

/// Collapsing home app bar: brand + actions, floating search pill that
/// hides on scroll down and returns on scroll up.
class HomeSliverAppBar extends StatelessWidget {
  const HomeSliverAppBar(
      {super.key, required this.storeName, this.logoUrl, this.showFavorites = true, this.cartCount = 0});
  final String storeName;
  final String? logoUrl;
  final bool showFavorites;
  final int cartCount;

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final logo = logoUrl != null && logoUrl!.isNotEmpty
        ? SizedBox(height: 32, child: NetImage(logoUrl, fit: BoxFit.contain))
        : Text(storeName,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(color: s.text, fontWeight: FontWeight.w900, fontSize: TypeScale.title + 2));
    return SliverAppBar(
      key: const Key('home_app_bar'),
      floating: true,
      snap: true,
      pinned: false,
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
      scrolledUnderElevation: Elevation.card,
      titleSpacing: Space.gutter,
      toolbarHeight: 56,
      title: Align(alignment: AlignmentDirectional.centerStart, child: logo),
      actions: [
        if (showFavorites)
          IconButton(
              key: const Key('header_favorites'),
              tooltip: 'Favorites',
              onPressed: () => SkinScope.open(context, '/favorites'),
              icon: Icon(Icons.favorite_border, color: s.text)),
        IconButton(
            key: const Key('header_cart'),
            tooltip: 'Cart',
            onPressed: () => SkinScope.open(context, '/cart'),
            icon: Badge(
                isLabelVisible: cartCount > 0,
                label: Text('$cartCount'),
                child: Icon(Icons.shopping_bag_outlined, color: s.text))),
        const SizedBox(width: Space.x2),
      ],
      bottom: PreferredSize(
        preferredSize: const Size.fromHeight(kMinTouch + Space.x3),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(Space.gutter, 0, Space.gutter, Space.x3),
          child: Semantics(
            button: true,
            label: 'Search products',
            child: GestureDetector(
              key: const Key('header_search'),
              onTap: () => SkinScope.open(context, '/search'),
              child: Container(
                height: kMinTouch,
                padding: const EdgeInsets.symmetric(horizontal: Space.x4),
                decoration: BoxDecoration(
                    color: s.searchFilled ? s.muted : Colors.white,
                    border: s.searchFilled ? null : Border.all(color: s.border),
                    borderRadius: BorderRadius.circular(s.searchRadius)),
                child: Row(children: [
                  Icon(Icons.search, size: 20, color: s.subtitleAA),
                  const SizedBox(width: Space.x2),
                  Expanded(
                      child: Text('Search products',
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: s.subtitleAA, fontSize: TypeScale.body))),
                ]),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Bottom navigation (Home / Categories / Cart w/ badge / Favorites / Account).
/// [keys] lets the mobile settings choose and order the tabs.
class AppBottomNav extends StatelessWidget {
  const AppBottomNav(
      {super.key, this.current = 0, this.showFavorites = true, this.cartCount = 0, this.keys});
  final int current;
  final bool showFavorites;
  final int cartCount;
  final List<String>? keys;

  static const _all = {
    'home': (Icons.home_outlined, Icons.home, 'Home', '/home'),
    'categories': (Icons.grid_view_outlined, Icons.grid_view, 'Categories', '/category/all'),
    'cart': (Icons.shopping_bag_outlined, Icons.shopping_bag, 'Cart', '/cart'),
    'favorites': (Icons.favorite_border, Icons.favorite, 'Favorites', '/favorites'),
    'offers': (Icons.percent, Icons.percent, 'Offers', '/list/offers'),
    'account': (Icons.person_outline, Icons.person, 'Account', '/account'),
  };
  static const _default = ['home', 'categories', 'cart', 'favorites', 'account'];

  /// Parses `settings.bottom_nav` (list of keys or {key} maps); falls back to defaults.
  static List<String> parseKeys(Object? raw) {
    final out = <String>[];
    if (raw is List) {
      for (final e in raw) {
        final k = e is Map ? '${e['key'] ?? e['id'] ?? ''}' : '$e';
        if (_all.containsKey(k) && !out.contains(k)) out.add(k);
      }
    }
    return out.length >= 2 ? out.take(5).toList() : _default;
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final items = [
      for (final k in keys ?? _default)
        if ((showFavorites || k != 'favorites') && _all[k] != null) (k, _all[k]!)
    ];
    // `current` indexes the default order (Home=0, Categories=1, Cart=2, Fav=3, Account=4).
    final currentKey = current >= 0 && current < _default.length ? _default[current] : '';
    return Container(
      decoration: BoxDecoration(color: Colors.white, boxShadow: Elevation.shadow(Elevation.bar)),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 60,
          child: Row(children: [
            for (final (k, it) in items)
              Expanded(
                child: Semantics(
                  button: true,
                  selected: k == currentKey,
                  label: it.$3,
                  child: InkWell(
                    key: Key('nav_${it.$3.toLowerCase()}'),
                    onTap: () {
                      if (k != currentKey) Haptics.tap();
                      SkinScope.open(context, it.$4);
                    },
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(minHeight: kMinTouch),
                      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Badge(
                          isLabelVisible: k == 'cart' && cartCount > 0,
                          label: Text('$cartCount'),
                          child: Icon(k == currentKey ? it.$2 : it.$1,
                              size: 24, color: k == currentKey ? s.primaryAA : s.subtitleAA),
                        ),
                        const SizedBox(height: 2),
                        Text(context.tr(it.$3),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: k == currentKey ? s.primaryAA : s.subtitleAA)),
                      ]),
                    ),
                  ),
                ),
              ),
          ]),
        ),
      ),
    );
  }
}
