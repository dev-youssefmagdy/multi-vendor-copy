import 'package:flutter/material.dart';
import '../../core/l10n/strings.dart';
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

/// Bottom navigation shell (Home / Categories / Cart / Favorites / Account).
class AppBottomNav extends StatelessWidget {
  const AppBottomNav({super.key, this.current = 0, this.showFavorites = true});
  final int current;
  final bool showFavorites;

  static const _items = [
    (Icons.home_outlined, Icons.home, 'Home', '/home'),
    (Icons.grid_view_outlined, Icons.grid_view, 'Categories', '/category/all'),
    (Icons.shopping_bag_outlined, Icons.shopping_bag, 'Cart', '/cart'),
    (Icons.favorite_border, Icons.favorite, 'Favorites', '/favorites'),
    (Icons.person_outline, Icons.person, 'Account', '/account'),
  ];

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final items = [
      for (final it in _items)
        if (showFavorites || it.$3 != 'Favorites') it
    ];
    return Container(
      decoration: BoxDecoration(color: Colors.white, boxShadow: [
        BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 10, offset: const Offset(0, -2))
      ]),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 58,
          child: Row(children: [
            for (var i = 0; i < items.length; i++)
              Expanded(
                child: InkWell(
                  key: Key('nav_${items[i].$3.toLowerCase()}'),
                  onTap: () => SkinScope.open(context, items[i].$4),
                  child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                    Icon(i == current ? items[i].$2 : items[i].$1,
                        size: 22, color: i == current ? s.primary : s.subtitle),
                    const SizedBox(height: 2),
                    Text(context.tr(items[i].$3),
                        style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: i == current ? s.primary : s.subtitle)),
                  ]),
                ),
              ),
          ]),
        ),
      ),
    );
  }
}
