import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../app/providers.dart';
import '../../core/l10n/strings.dart';
import '../../design/design.dart';
import '../../themes/shared/skin.dart';
import '../cart/cart_screen.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/widgets/shop_widgets.dart';

/// S06: address -> shipping quote -> payment method -> place order.
class CheckoutScreen extends StatelessWidget {
  const CheckoutScreen({super.key});
  @override
  Widget build(BuildContext context) => ShopScaffold(
    title: context.tr('Checkout'),
    body: const RequireAuth(from: '/checkout', child: _CheckoutBody()),
  );
}

class _CheckoutBody extends ConsumerStatefulWidget {
  const _CheckoutBody();
  @override
  ConsumerState<_CheckoutBody> createState() => _CheckoutBodyState();
}

class _CheckoutBodyState extends ConsumerState<_CheckoutBody> {
  bool _started = false;
  int _step = 0;

  Future<void> _start(List<Map<String, dynamic>> addresses) async {
    _started = true;
    final def =
        addresses.where((a) => a['is_default'] == true).firstOrNull ??
        addresses.firstOrNull;
    await ref
        .read(checkoutProvider.notifier)
        .init(
          preferredAddress: (def?['id'] as num?)?.toInt(),
          countryId: (def?['country_id'] as num?)?.toInt(),
        );
  }

  Future<void> _place(List<Map<String, dynamic>> addresses) async {
    final st = ref.read(checkoutProvider);
    final a = addresses.firstWhere(
      (e) => (e['id'] as num?)?.toInt() == st.addressId,
    );
    final profile = ref.read(profileProvider).value ?? const {};
    final shipping = {
      'name': a['full_name'] ?? profile['full_name'],
      'email': profile['email'],
      'phone': (a['phone'] as String?)?.isNotEmpty == true
          ? a['phone']
          : profile['phone'],
      'address': [
        a['address_line_1'],
        a['city'],
        a['state'],
        a['country'],
      ].where((e) => e != null && '$e'.isNotEmpty).join(', '),
    };
    final order = await ref.read(checkoutProvider.notifier).place(shipping);
    if (!mounted) return;
    final st2 = ref.read(checkoutProvider);
    if (order != null) {
      // Online gateways go to the WebView; the order result is decided by the
      // server (payment-status), never by this screen.
      openRoute(
        context,
        order.requiresPayment
            ? '/payment/${order.uuid}'
            : '/orders/${order.uuid}?placed=1',
      );
    } else if (st2.priceChanged) {
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          key: const Key('price_changed_dialog'),
          title: Text(context.tr('Prices have changed')),
          content: Text(
            context.tr(
              'The total is now :total. Please review your order before confirming.',
              {
                'total': formatPrice(
                  ref.read(currencySymbolProvider),
                  st2.quote?.grandTotal,
                ),
              },
            ),
          ),
          actions: [
            TextButton(
              key: const Key('price_changed_ok'),
              onPressed: () => Navigator.pop(ctx),
              child: Text(context.tr('Review order')),
            ),
          ],
        ),
      );
      ref.read(checkoutProvider.notifier).ackPriceChange();
    }
  }

  Future<void> _pickAddress(List<Map<String, dynamic>> addresses) async {
    final skin = SkinScope.of(context);
    final id = await showModalBottomSheet<int>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => SkinScope(
        skin: skin,
        child: SafeArea(
          child: ListView(
            shrinkWrap: true,
            children: [
              for (final a in addresses)
                ListTile(
                  key: Key('address_${a['id']}'),
                  minTileHeight: kMinTouch,
                  leading: Icon(
                    (a['id'] as num?)?.toInt() == ref.read(checkoutProvider).addressId
                        ? Icons.radio_button_checked
                        : Icons.radio_button_off,
                  ),
                  title: Text('${a['full_name'] ?? ''}'),
                  subtitle: Text([a['address_line_1'], a['city'], a['country']].where((e) => e != null).join(', ')),
                  onTap: () => Navigator.pop(ctx, (a['id'] as num).toInt()),
                ),
            ],
          ),
        ),
      ),
    );
    if (id == null || !mounted) return;
    final a = addresses.firstWhere((e) => (e['id'] as num).toInt() == id);
    ref.read(checkoutProvider.notifier).selectAddress(id, (a['country_id'] as num?)?.toInt());
  }

  Future<void> _pickGateway() async {
    final skin = SkinScope.of(context);
    final st = ref.read(checkoutProvider);
    final g = await showModalBottomSheet<Gateway>(
      context: context,
      builder: (ctx) => SkinScope(
        skin: skin,
        child: SafeArea(
          child: ListView(
            shrinkWrap: true,
            children: [
              for (final g in st.gateways)
                ListTile(
                  key: Key('gateway_${g.code}'),
                  minTileHeight: kMinTouch,
                  leading: Icon(g == st.gateway ? Icons.radio_button_checked : Icons.radio_button_off),
                  title: Text(g.isCod ? context.tr('Cash on delivery') : g.name),
                  onTap: () => Navigator.pop(ctx, g),
                ),
            ],
          ),
        ),
      ),
    );
    if (g != null && mounted) ref.read(checkoutProvider.notifier).selectGateway(g);
  }

  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    // Store switched: the checkout state was reset, so start over.
    ref.listen(sessionEpochProvider, (_, _) => _started = false);
    final addrAsync = ref.watch(addressesProvider);
    ref.watch(profileProvider);
    final st = ref.watch(checkoutProvider);
    return AsyncBody<List<Map<String, dynamic>>>(
      addrAsync,
      onRetry: () => ref.invalidate(addressesProvider),
      data: (addresses) {
        if (!_started) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted && !_started) _start(addresses);
          });
        }
        if (addresses.isEmpty) {
          return EmptyView(
            context.tr('Add a shipping address to continue'),
            icon: Icons.location_on_outlined,
            actionLabel: context.tr('Add address'),
            onAction: () => openRoute(context, '/account/addresses'),
          );
        }
        final q = st.quote;
        final addr = addresses
            .where((e) => (e['id'] as num?)?.toInt() == st.addressId)
            .firstOrNull;
        final sym = ref.watch(currencySymbolProvider);
        final canNext = _step == 0
            ? st.addressId != null
            : _step == 1
            ? st.gateway != null
            : false;
        return Column(
          children: [
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _Steps(
                    current: _step,
                    onTap: (i) {
                      if (i < _step) setState(() => _step = i);
                    },
                    done: [
                      st.addressId != null && _step > 0,
                      st.gateway != null && _step > 1,
                      q != null && !st.loading && _step > 2,
                    ],
                    labels: [
                      context.tr('Address'),
                      context.tr('Payment'),
                      context.tr('Review'),
                    ],
                  ),
                  const SizedBox(height: Space.x4),
                  if (_step == 0) ...[
                    _h(context.tr('Shipping address'), s),
                    _PickCard(
                      key: const Key('address_card'),
                      icon: Icons.location_on_outlined,
                      title: '${addr?['full_name'] ?? ''}',
                      subtitle: [addr?['address_line_1'], addr?['city'], addr?['country']]
                          .where((e) => e != null)
                          .join(', '),
                      action: context.tr('Change'),
                      onTap: st.placing ? null : () => _pickAddress(addresses),
                    ),
                    TextButton(
                      onPressed: () => openRoute(context, '/account/addresses'),
                      child: Text(context.tr('Manage addresses')),
                    ),
                  ],
                  if (_step == 1) ...[
                    _h(context.tr('Payment method'), s),
                    if (st.gateways.isEmpty && !st.loading)
                      Text(context.tr('No payment methods available')),
                    if (st.gateway != null)
                      _PickCard(
                        key: const Key('gateway_card'),
                        icon: Icons.payment_outlined,
                        title: st.gateway!.isCod
                            ? context.tr('Cash on delivery')
                            : st.gateway!.name,
                        subtitle: '',
                        action: context.tr('Change'),
                        onTap: st.placing ? null : _pickGateway,
                      ),
                    const SizedBox(height: Space.x2),
                    if (q != null) ...[
                      _h(context.tr('Shipping'), s),
                      if (q.shipping != null && q.shipping!.available)
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.local_shipping_outlined),
                          title: Text(context.tr('Shipping')),
                          trailing: Price(q.shipping!.amount),
                        ),
                      if (q.shipping != null && !q.shipping!.available)
                        Text(
                          q.shipping!.message ?? context.tr('Shipping is not available to this address'),
                          key: const Key('shipping_unavailable'),
                          style: TextStyle(color: s.error),
                        ),
                    ],
                  ],
                  if (_step == 2) ...[
                    _h(context.tr('Review order'), s),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.location_on_outlined),
                      title: Text('${addr?['full_name'] ?? ''}'),
                      subtitle: Text([addr?['address_line_1'], addr?['city']].where((e) => e != null).join(', ')),
                    ),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.payment_outlined),
                      title: Text(st.gateway == null
                          ? ''
                          : (st.gateway!.isCod ? context.tr('Cash on delivery') : st.gateway!.name)),
                    ),
                    if (st.loading && q == null)
                      const LoadingView()
                    else if (q != null) ...[
                      Totals(q),
                      if (q.shipping != null && !q.shipping!.available)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            q.shipping!.message ?? context.tr('Shipping is not available to this address'),
                            key: const Key('shipping_unavailable'),
                            style: TextStyle(color: s.error),
                          ),
                        ),
                      if (q.hasUnavailable)
                        Text(
                          context.tr('Some items are out of stock. Update your cart.'),
                          style: TextStyle(color: s.error),
                        ),
                    ],
                  ],
                  if (st.error != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(st.error!, key: const Key('checkout_error'), style: TextStyle(color: s.error)),
                    ),
                ],
              ),
            ),
            Material(
              elevation: Elevation.sheet,
              color: s.surface,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      if (_step > 0)
                        Padding(
                          padding: const EdgeInsetsDirectional.only(end: Space.x2),
                          child: IconButton(
                            key: const Key('step_back'),
                            tooltip: context.tr('Back'),
                            onPressed: st.placing ? null : () => setState(() => _step--),
                            icon: const Icon(Icons.arrow_back),
                          ),
                        ),
                      Expanded(
                        child: _step < 2
                            ? SkinButton(
                                key: const Key('step_next'),
                                label: context.tr('Continue'),
                                onPressed: canNext ? () => setState(() => _step++) : null,
                              )
                            : SkinButton(
                                key: const Key('place_order'),
                                label: q == null
                                    ? context.tr('Place order')
                                    : '${context.tr('Place order')} · ${formatPrice(sym, q.grandTotal)}',
                                busy: st.placing,
                                onPressed: st.canPlace ? () => _place(addresses) : null,
                              ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _h(String t, ThemeSkin s) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(
      t,
      style: TextStyle(
        color: s.text,
        fontWeight: FontWeight.w800,
        fontSize: 16,
      ),
    ),
  );
}

/// Selected-value card with a "Change" action (opens a bottom-sheet picker).
class _PickCard extends StatelessWidget {
  const _PickCard({super.key, required this.icon, required this.title, required this.subtitle, required this.action, this.onTap});
  final IconData icon;
  final String title, subtitle, action;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Card(
      elevation: 0,
      color: s.surface,
      shape: RoundedRectangleBorder(
          side: BorderSide(color: s.border), borderRadius: BorderRadius.circular(s.radius)),
      child: ListTile(
        minTileHeight: kMinTouch + Space.x4,
        leading: Icon(icon, color: s.primaryAA),
        title: Text(title),
        subtitle: subtitle.isEmpty ? null : Text(subtitle),
        trailing: TextButton(onPressed: onTap, child: Text(action)),
        onTap: onTap,
      ),
    );
  }
}

/// Three-step progress indicator (address -> payment -> review).
class _Steps extends StatelessWidget {
  const _Steps({required this.done, required this.labels, required this.current, required this.onTap});
  final List<bool> done;
  final List<String> labels;
  final int current;
  final ValueChanged<int> onTap;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Semantics(
      label: 'Checkout steps',
      child: Row(children: [
        for (var i = 0; i < labels.length; i++) ...[
          if (i > 0) Expanded(child: Divider(color: done[i - 1] ? s.primary : s.border, thickness: 2)),
          InkWell(
            key: Key('step_$i'),
            onTap: () => onTap(i),
            child: Column(children: [
              CircleAvatar(
                  radius: 14,
                  backgroundColor: done[i] || i == current ? s.primary : s.muted,
                  child: done[i]
                      ? Icon(Icons.check, size: 16, color: s.onPrimary)
                      : Text('${i + 1}',
                          style: TextStyle(color: i == current ? s.onPrimary : s.text, fontSize: 12))),
              const SizedBox(height: 2),
              Text(labels[i],
                  style: TextStyle(
                      fontSize: TypeScale.caption,
                      fontWeight: i == current ? FontWeight.w800 : FontWeight.w400,
                      color: s.text)),
            ]),
          ),
        ],
      ]),
    );
  }
}
