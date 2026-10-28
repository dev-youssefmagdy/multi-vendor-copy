import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/errors/app_exception.dart';
import '../../../core/l10n/strings.dart';
import '../../../core/platform/html_text.dart';
import '../../../themes/shared/primitives.dart';
import '../../../themes/shared/product_card.dart';
import '../../../themes/shared/skin.dart';
import '../commerce_models.dart';
import '../commerce_providers.dart';
import '../widgets/shop_widgets.dart';

/// S04: product detail (gallery, variants, qty, add to cart, reviews, related).
class ProductScreen extends ConsumerWidget {
  const ProductScreen(this.slug, {super.key});
  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(productProvider(slug));
    return ShopScaffold(
      title: '',
      actions: [
        IconButton(
            key: const Key('goto_cart'),
            onPressed: () => openRoute(context, '/cart'),
            icon: Badge(
                isLabelVisible: ref.watch(cartCountProvider) > 0,
                label: Text('${ref.watch(cartCountProvider)}'),
                child: const Icon(Icons.shopping_bag_outlined))),
      ],
      body: AsyncBody<ProductDetail>(async,
          onRetry: () => ref.invalidate(productProvider(slug)),
          data: (d) => _ProductBody(d)),
    );
  }
}

class _ProductBody extends ConsumerStatefulWidget {
  const _ProductBody(this.detail);
  final ProductDetail detail;
  @override
  ConsumerState<_ProductBody> createState() => _ProductBodyState();
}

class _ProductBodyState extends ConsumerState<_ProductBody> {
  Variant? _variant;
  int _qty = 1;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _variant = widget.detail.variants.where((v) => v.available).firstOrNull ??
        widget.detail.variants.firstOrNull;
  }

  bool get _hasVariants => widget.detail.variants.isNotEmpty;
  bool get _canBuy => !_hasVariants || (_variant?.available ?? false);

  Future<bool> _add() async {
    setState(() => _busy = true);
    try {
      await ref
          .read(cartProvider.notifier)
          .add(widget.detail.item, variantId: _variant?.id, qty: _qty);
      return true;
    } on AppException catch (e) {
      if (mounted) showError(context, e);
      return false;
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final d = widget.detail;
    final p = d.item;
    final desc = htmlToText(d.description);
    return ListView(children: [
      Stack(children: [
        AspectRatio(
            aspectRatio: 1,
            child: ColoredBox(
                color: s.muted, child: NetImage(p.image, fit: BoxFit.contain))),
        PositionedDirectional(
            top: 12, end: 12, child: FavoriteHeart(p.id, size: 22)),
        if (p.hasDiscount)
          PositionedDirectional(
              top: 12,
              start: 12,
              child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                      color: s.error, borderRadius: BorderRadius.circular(s.radius)),
                  child: Text('-${p.discountPercent}%',
                      style: const TextStyle(
                          color: Colors.white, fontWeight: FontWeight.w700)))),
      ]),
      Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(p.name,
              key: const Key('product_name'),
              style: TextStyle(
                  color: s.text, fontSize: 20, fontWeight: FontWeight.w800, height: 1.25)),
          const SizedBox(height: 8),
          Row(children: [
            Price(p.price,
                style: TextStyle(
                    color: p.hasDiscount ? s.primary : s.text,
                    fontSize: 22,
                    fontWeight: FontWeight.w900)),
            if (p.hasDiscount) ...[
              const SizedBox(width: 8),
              Price(p.basePrice,
                  style: TextStyle(
                      color: s.subtitle, decoration: TextDecoration.lineThrough)),
            ],
          ]),
          if (d.ratingCount > 0) ...[
            const SizedBox(height: 6),
            Row(children: [
              RatingStars(d.ratingAvg, size: 16),
              const SizedBox(width: 6),
              Text('(${d.ratingCount})', style: TextStyle(color: s.subtitle)),
            ]),
          ],
          if (_hasVariants) ...[
            const SizedBox(height: 16),
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final v in d.variants)
                ChoiceChip(
                    key: Key('variant_${v.id}'),
                    label: Text(v.title),
                    selected: _variant?.id == v.id,
                    selectedColor: s.primary.withValues(alpha: 0.15),
                    onSelected: v.available ? (_) => setState(() => _variant = v) : null),
            ]),
          ],
          if (!_canBuy)
            Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Text(context.tr('Out of stock'),
                    key: const Key('out_of_stock'),
                    style: TextStyle(color: s.error, fontWeight: FontWeight.w700))),
          const SizedBox(height: 16),
          Row(children: [
            _QtyStepper(_qty, (v) => setState(() => _qty = v)),
            const SizedBox(width: 12),
            Expanded(
                child: SkinButton(
                    key: const Key('add_to_cart'),
                    label: context.tr('Add to cart'),
                    icon: Icons.add_shopping_cart,
                    busy: _busy,
                    onPressed: _canBuy
                        ? () async {
                            if (await _add() && context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(context.tr('Added to cart'))));
                            }
                          }
                        : null)),
          ]),
          const SizedBox(height: 8),
          SkinButton(
              key: const Key('buy_now'),
              outline: true,
              label: context.tr('Buy now'),
              onPressed: _canBuy && !_busy
                  ? () async {
                      if (await _add() && context.mounted) openRoute(context, '/cart');
                    }
                  : null),
          if (desc.isNotEmpty) ...[
            const SizedBox(height: 24),
            Text(context.tr('Description'),
                style: TextStyle(color: s.text, fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 6),
            Text(desc, style: TextStyle(color: s.text, height: 1.5)),
          ],
          const SizedBox(height: 24),
          _Reviews(d),
        ]),
      ),
      const _Related(),
      const SizedBox(height: 24),
    ]);
  }
}

class _QtyStepper extends StatelessWidget {
  const _QtyStepper(this.qty, this.onChanged);
  final int qty;
  final ValueChanged<int> onChanged;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Container(
      decoration: BoxDecoration(
          border: Border.all(color: s.border),
          borderRadius: BorderRadius.circular(s.radius)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        IconButton(
            key: const Key('qty_minus'),
            icon: const Icon(Icons.remove),
            onPressed: qty > 1 ? () => onChanged(qty - 1) : null),
        Text('$qty', key: const Key('qty_value')),
        IconButton(
            key: const Key('qty_plus'),
            icon: const Icon(Icons.add),
            onPressed: qty < 99 ? () => onChanged(qty + 1) : null),
      ]),
    );
  }
}

class _Reviews extends ConsumerStatefulWidget {
  const _Reviews(this.detail);
  final ProductDetail detail;
  @override
  ConsumerState<_Reviews> createState() => _ReviewsState();
}

class _ReviewsState extends ConsumerState<_Reviews> {
  final List<Review> _items = [];
  int _page = 0, _last = 1;
  bool _loading = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    Future.microtask(_more);
  }

  Future<void> _more() async {
    if (_loading || (_page >= _last && _page != 0)) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await ref
          .read(shopRepositoryProvider)
          .reviews(widget.detail.numericId, _page + 1);
      if (!mounted) return;
      setState(() {
        _items.addAll(r.reviews);
        _page = r.page;
        _last = r.lastPage;
        _loading = false;
      });
    } on AppException catch (e) {
      if (mounted) {
        setState(() {
          _error = e;
          _loading = false;
        });
      }
    }
  }

  Future<void> _write() async {
    if (!ref.read(isAuthenticatedProvider)) {
      openRoute(context, '/login');
      return;
    }
    var stars = 5;
    final ctl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, set) => AlertDialog(
          title: Text(context.tr('Write a review')),
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              for (var i = 1; i <= 5; i++)
                IconButton(
                    onPressed: () => set(() => stars = i),
                    icon: Icon(i <= stars ? Icons.star : Icons.star_border,
                        color: Colors.amber)),
            ]),
            TextField(controller: ctl, maxLines: 3),
          ]),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(context.tr('Cancel'))),
            TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(context.tr('Save'))),
          ],
        ),
      ),
    );
    final comment = ctl.text;
    ctl.dispose();
    if (ok != true) return;
    try {
      await ref
          .read(accountRepositoryProvider)
          .submitReview(widget.detail.numericId, stars, comment);
      _items.clear();
      _page = 0;
      _last = 1;
      await _more();
    } on AppException catch (e) {
      if (mounted) showError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Expanded(
            child: Text(context.tr('Reviews'),
                style: TextStyle(color: s.text, fontWeight: FontWeight.w800, fontSize: 16))),
        TextButton(
            key: const Key('write_review'),
            onPressed: _write,
            child: Text(context.tr('Write a review'))),
      ]),
      for (final r in _items)
        ListTile(
          key: Key('review_${r.id}'),
          contentPadding: EdgeInsets.zero,
          title: Row(children: [
            RatingStars(r.stars.toDouble(), size: 14),
            const SizedBox(width: 8),
            Text(r.author ?? '', style: const TextStyle(fontSize: 13)),
          ]),
          subtitle: r.comment == null ? null : Text(r.comment!),
        ),
      if (_loading) const Center(child: CircularProgressIndicator(strokeWidth: 2)),
      if (_error != null) ErrorView(_error!, onRetry: _more),
      if (!_loading && _items.isEmpty && _error == null)
        Text(context.tr('No reviews yet'), style: TextStyle(color: s.subtitle)),
      if (!_loading && _page < _last)
        TextButton(onPressed: _more, child: Text(context.tr('Load more'))),
    ]);
  }
}

class _Related extends ConsumerWidget {
  const _Related();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(relatedProductsProvider);
    return async.maybeWhen(
      data: (items) => items.isEmpty
          ? const SizedBox.shrink()
          : Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Text(context.tr('Related products'),
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16))),
              const SizedBox(height: 8),
              ProductRow(items),
            ]),
      orElse: () => const SizedBox.shrink(),
    );
  }
}
