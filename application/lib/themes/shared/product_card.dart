import 'package:flutter/material.dart';
import 'models.dart';
import 'primitives.dart';
import 'skin.dart';

/// One product card; layout switches on [ThemeSkin.cardStyle]
/// (ecommet `_product-card`, elora base/v2..v6 `product_card` partials).
class ProductCard extends StatelessWidget {
  const ProductCard(this.product, {super.key, this.width, this.badge});
  final ProductItem product;
  final double? width;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final p = product;
    final style = s.cardStyle;
    final w = width ?? s.cardWidth;
    final r = BorderRadius.circular(s.cardRadius);
    final imgH = style == CardStyle.square ? w : s.cardImageHeight;

    final shadow = switch (style) {
      CardStyle.shadow || CardStyle.compact || CardStyle.square => [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 8,
              offset: const Offset(0, 2))
        ],
      CardStyle.flat => [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 3)
        ],
      _ => const <BoxShadow>[],
    };

    final pct = p.discountPercent;
    final badgeText = badge ?? (pct > 0 ? '-$pct%' : null);
    final small = style == CardStyle.compact;

    final image = Stack(children: [
      Positioned.fill(
          child: ClipRRect(
              borderRadius: BorderRadius.vertical(top: r.topLeft),
              child: ColoredBox(
                  color: s.themeSlug == 'ecommet' ? const Color(0xFFF8F8F8) : s.muted,
                  child: NetImage(p.image, fit: s.imageFit)))),
      if (badgeText != null)
        PositionedDirectional(
            top: 0,
            start: 0,
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: small ? 5 : 8, vertical: 3),
              decoration: BoxDecoration(
                  color: style == CardStyle.base || style == CardStyle.flat
                      ? s.error
                      : s.primary,
                  borderRadius: BorderRadiusDirectional.only(
                      bottomEnd: Radius.circular(s.cardRadius.clamp(4, 8)),
                      topStart: Radius.circular(style == CardStyle.overlay ? 0 : s.cardRadius))),
              child: Text(badgeText,
                  style: TextStyle(
                      color: Colors.white,
                      fontSize: small ? 8 : 11,
                      fontWeight: FontWeight.w600)),
            )),
      if (style == CardStyle.overlay)
        PositionedDirectional(
            top: 6,
            end: 6,
            child: Container(
              width: 28,
              height: 28,
              decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
              child: Icon(Icons.favorite_border, size: 15, color: s.text),
            )),
    ]);

    final body = Padding(
      padding: EdgeInsets.all(small ? 6 : 10),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(p.name,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
                color: s.text,
                fontSize: small ? 11 : 13,
                height: 1.25,
                fontWeight: FontWeight.w500)),
        if (p.rating != null) ...[
          const SizedBox(height: 4),
          RatingStars(p.rating!, size: small ? 10 : 13)
        ],
        const SizedBox(height: 4),
        Wrap(crossAxisAlignment: WrapCrossAlignment.center, spacing: 6, children: [
          Text(money(p.price),
              style: TextStyle(
                  color: p.hasDiscount || p.isFlash ? s.primary : s.text,
                  fontSize: small ? 12 : 15,
                  fontWeight: FontWeight.w800)),
          if (p.hasDiscount)
            Text(money(p.basePrice),
                style: TextStyle(
                    color: s.subtitle,
                    fontSize: small ? 9 : 12,
                    decoration: TextDecoration.lineThrough)),
        ]),
      ]),
    );

    return GestureDetector(
      key: Key('product_${p.slug}'),
      onTap: () => SkinScope.open(context, productRoute(p)),
      child: Container(
        width: w,
        decoration: BoxDecoration(
            color: style == CardStyle.overlay || style == CardStyle.square
                ? Colors.white
                : s.background,
            borderRadius: r,
            boxShadow: shadow),
        child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [SizedBox(height: imgH, width: w, child: image), body]),
      ),
    );
  }
}

/// Horizontal rail of cards.
class ProductRow extends StatelessWidget {
  const ProductRow(this.products, {super.key, this.badge});
  final List<ProductItem> products;
  final String? badge;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final h = (s.cardStyle == CardStyle.square ? s.cardWidth : s.cardImageHeight) + 110;
    return SizedBox(
      height: h,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        itemCount: products.length,
        separatorBuilder: (_, _) => const SizedBox(width: 10),
        itemBuilder: (_, i) => ProductCard(products[i], badge: badge),
      ),
    );
  }
}

/// 2-column grid (ecommet `grid-cols-2 gap-2`).
class ProductGrid extends StatelessWidget {
  const ProductGrid(this.products, {super.key});
  final List<ProductItem> products;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return LayoutBuilder(builder: (context, c) {
      final gap = 8.0;
      final w = (c.maxWidth - 32 - gap) / 2;
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Wrap(spacing: gap, runSpacing: gap, children: [
          for (final p in products)
            ProductCard(p, width: w.clamp(100, s.cardWidth * 2).toDouble()),
        ]),
      );
    });
  }
}
