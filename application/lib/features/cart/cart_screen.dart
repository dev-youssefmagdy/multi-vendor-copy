import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../../design/design.dart';
import '../../themes/shared/primitives.dart';
import '../../themes/shared/skin.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/widgets/shop_widgets.dart';

/// S05: cart. Signed-in => server cart (authoritative totals). Guest => device
/// cart with estimated subtotal; checkout asks the customer to sign in first.
class CartScreen extends ConsumerWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(cartProvider);
    return ShopScaffold(
      title: context.tr('Cart'),
      navIndex: 2,
      body: AsyncBody<CartData>(
        async,
        onRetry: () => ref.invalidate(cartProvider),
        data: (cart) => cart.isEmpty
            ? EmptyView(
                context.tr('Your cart is empty'),
                icon: Icons.shopping_bag_outlined,
                actionLabel: context.tr('Continue shopping'),
                onAction: () => openRoute(context, '/home'),
              )
            : _CartBody(cart),
      ),
    );
  }
}

class _CartBody extends ConsumerStatefulWidget {
  const _CartBody(this.cart);
  final CartData cart;
  @override
  ConsumerState<_CartBody> createState() => _CartBodyState();
}

class _CartBodyState extends ConsumerState<_CartBody> {
  final _coupon = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _coupon.dispose();
    super.dispose();
  }

  Future<void> _run(Future<void> Function() op) async {
    setState(() => _busy = true);
    try {
      await op();
    } on AppException catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = widget.cart;
    final s = SkinScope.of(context);
    final ctl = ref.read(cartProvider.notifier);
    final authed = ref.watch(isAuthenticatedProvider);
    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: ctl.reload,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (cart.freeShippingPct != null &&
                    cart.freeShippingPct! > 0 &&
                    cart.freeShippingPct! < 100)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: LinearProgressIndicator(
                      value: cart.freeShippingPct! / 100,
                      color: s.primary,
                    ),
                  ),
                for (final l in cart.items)
                  _LineTile(l, busy: _busy, run: _run),
                const SizedBox(height: 12),
                if (authed) _couponBox(cart, s),
                const SizedBox(height: 12),
                _Totals(cart, estimate: cart.isGuest),
              ],
            ),
          ),
        ),
        Material(
          elevation: Elevation.sheet,
          color: Colors.white,
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (!cart.isGuest)
                    Padding(
                      padding: const EdgeInsets.only(bottom: Space.x2),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              context.tr('Total'),
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          DefaultTextStyle.merge(
                            style: const TextStyle(
                              fontWeight: FontWeight.w900,
                              fontSize: 18,
                            ),
                            child: Price(cart.grandTotal),
                          ),
                        ],
                      ),
                    ),
                  SkinButton(
                    key: const Key('go_checkout'),
                    label: cart.isGuest
                        ? context.tr('Sign in to checkout')
                        : context.tr('Checkout'),
                    onPressed: cart.hasUnavailable || _busy
                        ? null
                        : () => openRoute(
                            context,
                            cart.isGuest
                                ? '/login?from=${Uri.encodeComponent('/checkout')}'
                                : '/checkout',
                          ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _couponSheet() async {
    final skin = SkinScope.of(context);
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => SkinScope(
        skin: skin,
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, 0, 16, 16 + MediaQuery.of(ctx).viewInsets.bottom),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            AppTextField(
                fieldKey: const Key('coupon_field'),
                controller: _coupon,
                label: context.tr('Coupon code'),
                textInputAction: TextInputAction.done),
            const SizedBox(height: Space.x3),
            SkinButton(
                key: const Key('coupon_apply'),
                label: context.tr('Apply'),
                onPressed: () async {
                  final code = _coupon.text.trim();
                  if (code.isEmpty) return;
                  try {
                    await ref.read(cartProvider.notifier).applyCoupon(code);
                    _coupon.clear();
                    if (ctx.mounted) Navigator.pop(ctx);
                  } on AppException catch (e) {
                    if (ctx.mounted) showError(ctx, e);
                  }
                }),
          ]),
        ),
      ),
    );
  }

  Widget _couponBox(CartData cart, ThemeSkin s) {
    if (cart.couponCode != null) {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border.all(color: cart.couponValid ? s.success : s.error),
          borderRadius: BorderRadius.circular(s.radius),
        ),
        child: Row(
          children: [
            Icon(Icons.sell_outlined, color: s.success),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                cart.couponValid
                    ? cart.couponCode!
                    : '${cart.couponCode!} (${cart.couponError ?? 'invalid'})',
                key: const Key('coupon_applied'),
              ),
            ),
            TextButton(
              key: const Key('coupon_remove'),
              onPressed: () =>
                  _run(() => ref.read(cartProvider.notifier).removeCoupon()),
              child: Text(context.tr('Remove')),
            ),
          ],
        ),
      );
    }
    return ListTile(
      key: const Key('coupon_open'),
      contentPadding: EdgeInsets.zero,
      minTileHeight: kMinTouch,
      leading: Icon(Icons.sell_outlined, color: s.primaryAA),
      title: Text(context.tr('Have a coupon?')),
      trailing: const Icon(Icons.chevron_right),
      onTap: _couponSheet,
    );
  }
}

class _LineTile extends ConsumerWidget {
  const _LineTile(this.line, {required this.busy, required this.run});
  final CartLine line;
  final bool busy;
  final Future<void> Function(Future<void> Function()) run;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SkinScope.of(context);
    final ctl = ref.read(cartProvider.notifier);
    return Dismissible(
      key: Key('line_${line.key}'),
      direction: busy ? DismissDirection.none : DismissDirection.endToStart,
      background: Container(
        color: s.error,
        alignment: AlignmentDirectional.centerEnd,
        padding: const EdgeInsets.symmetric(horizontal: Space.x5),
        child: const Icon(Icons.delete_outline, color: Colors.white),
      ),
      onDismissed: (_) {
        Haptics.warn();
        run(() => ctl.remove(line));
      },
      child: Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 72,
              height: 72,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(s.radius),
                child: NetImage(line.image),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    line.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: s.text,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Price(
                    line.unitPrice,
                    style: TextStyle(
                      color: s.primary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  if (line.outOfStock)
                    Text(
                      context.tr('Out of stock'),
                      key: Key('oos_${line.key}'),
                      style: TextStyle(color: s.error, fontSize: 12),
                    ),
                  Row(
                    children: [
                      IconButton(
                        key: Key('dec_${line.key}'),
                        constraints: const BoxConstraints(
                          minWidth: kMinTouch,
                          minHeight: kMinTouch,
                        ),
                        icon: const Icon(Icons.remove_circle_outline),
                        onPressed: busy
                            ? null
                            : () => run(() => ctl.setQty(line, line.qty - 1)),
                      ),
                      Text('${line.qty}', key: Key('qty_${line.key}')),
                      IconButton(
                        key: Key('inc_${line.key}'),
                        constraints: const BoxConstraints(
                          minWidth: kMinTouch,
                          minHeight: kMinTouch,
                        ),
                        icon: const Icon(Icons.add_circle_outline),
                        onPressed: busy
                            ? null
                            : () => run(() => ctl.setQty(line, line.qty + 1)),
                      ),
                      const Spacer(),
                      IconButton(
                        key: Key('remove_${line.key}'),
                        constraints: const BoxConstraints(
                          minWidth: kMinTouch,
                          minHeight: kMinTouch,
                        ),
                        icon: Icon(Icons.delete_outline, color: s.error),
                        onPressed: busy
                            ? null
                            : () => run(() => ctl.remove(line)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Server-provided totals (never recomputed on the device).
class Totals extends StatelessWidget {
  const Totals(this.cart, {super.key, this.estimate = false});
  final CartData cart;
  final bool estimate;
  @override
  Widget build(BuildContext context) => _Totals(cart, estimate: estimate);
}

class _Totals extends StatelessWidget {
  const _Totals(this.cart, {required this.estimate});
  final CartData cart;
  final bool estimate;
  @override
  Widget build(BuildContext context) {
    Widget row(String l, Widget v, {bool bold = false, Key? key}) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Expanded(
            child: Text(
              l,
              style: TextStyle(
                fontWeight: bold ? FontWeight.w800 : FontWeight.w400,
              ),
            ),
          ),
          DefaultTextStyle.merge(
            key: key,
            style: TextStyle(
              fontWeight: bold ? FontWeight.w900 : FontWeight.w500,
              fontSize: bold ? 17 : 14,
            ),
            child: v,
          ),
        ],
      ),
    );
    final ship = cart.shipping;
    return Column(
      children: [
        row(
          context.tr('Subtotal'),
          Price(cart.itemsTotal),
          key: const Key('total_items'),
        ),
        if (cart.couponDiscount > 0 && cart.couponValid)
          row(context.tr('Discount'), Price(-cart.couponDiscount)),
        if (ship != null)
          row(
            context.tr('Shipping'),
            ship.available
                ? Price(ship.amount)
                : Text(ship.message ?? context.tr('Unavailable')),
          ),
        if (cart.tax > 0) row(context.tr('Tax'), Price(cart.tax)),
        if (!estimate) ...[
          const Divider(),
          row(
            context.tr('Total'),
            Price(cart.grandTotal),
            bold: true,
            key: const Key('grand_total'),
          ),
        ] else
          Padding(
            padding: const EdgeInsets.only(top: 6),
            child: Text(
              context.tr('Final prices are confirmed after you sign in.'),
              style: TextStyle(
                color: SkinScope.of(context).subtitle,
                fontSize: 12,
              ),
            ),
          ),
      ],
    );
  }
}
