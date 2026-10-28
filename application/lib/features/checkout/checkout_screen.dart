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
        return Column(
          children: [
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _Steps(
                    done: [
                      st.addressId != null,
                      st.gateway != null,
                      q != null && !st.loading,
                    ],
                    labels: [
                      context.tr('Address'),
                      context.tr('Payment'),
                      context.tr('Review'),
                    ],
                  ),
                  const SizedBox(height: Space.x4),
                  _h('1  ${context.tr('Shipping address')}', s),
                  RadioGroup<int>(
                    groupValue: st.addressId,
                    onChanged: (v) {
                      if (v == null || st.placing) return;
                      final a = addresses.firstWhere(
                        (e) => (e['id'] as num).toInt() == v,
                      );
                      ref
                          .read(checkoutProvider.notifier)
                          .selectAddress(v, (a['country_id'] as num?)?.toInt());
                    },
                    child: Column(
                      children: [
                        for (final a in addresses)
                          RadioListTile<int>(
                            key: Key('address_${a['id']}'),
                            value: (a['id'] as num).toInt(),
                            title: Text('${a['full_name'] ?? ''}'),
                            subtitle: Text(
                              [
                                a['address_line_1'],
                                a['city'],
                                a['country'],
                              ].where((e) => e != null).join(', '),
                            ),
                          ),
                      ],
                    ),
                  ),
                  TextButton(
                    onPressed: () => openRoute(context, '/account/addresses'),
                    child: Text(context.tr('Manage addresses')),
                  ),
                  const SizedBox(height: 8),
                  _h('2  ${context.tr('Payment method')}', s),
                  if (st.gateways.isEmpty && !st.loading)
                    Text(context.tr('No payment methods available')),
                  RadioGroup<Gateway>(
                    groupValue: st.gateway,
                    onChanged: (v) {
                      if (v != null && !st.placing) {
                        ref.read(checkoutProvider.notifier).selectGateway(v);
                      }
                    },
                    child: Column(
                      children: [
                        for (final g in st.gateways)
                          RadioListTile<Gateway>(
                            key: Key('gateway_${g.code}'),
                            value: g,
                            title: Text(
                              g.isCod ? context.tr('Cash on delivery') : g.name,
                            ),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  _h('3  ${context.tr('Review order')}', s),
                  if (st.loading && q == null)
                    const LoadingView()
                  else if (q != null) ...[
                    Totals(q),
                    if (q.shipping != null && !q.shipping!.available)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          q.shipping!.message ??
                              context.tr(
                                'Shipping is not available to this address',
                              ),
                          key: const Key('shipping_unavailable'),
                          style: TextStyle(color: s.error),
                        ),
                      ),
                    if (q.hasUnavailable)
                      Text(
                        context.tr(
                          'Some items are out of stock. Update your cart.',
                        ),
                        style: TextStyle(color: s.error),
                      ),
                  ],
                  if (st.error != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        st.error!,
                        key: const Key('checkout_error'),
                        style: TextStyle(color: s.error),
                      ),
                    ),
                ],
              ),
            ),
            Material(
              elevation: Elevation.sheet,
              color: Colors.white,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: SkinButton(
                    key: const Key('place_order'),
                    label: q == null
                        ? context.tr('Place order')
                        : '${context.tr('Place order')} · ${formatPrice(ref.watch(currencySymbolProvider), q.grandTotal)}',
                    busy: st.placing,
                    onPressed: st.canPlace ? () => _place(addresses) : null,
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

/// Three-step progress indicator (address -> payment -> review).
class _Steps extends StatelessWidget {
  const _Steps({required this.done, required this.labels});
  final List<bool> done;
  final List<String> labels;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    return Semantics(
      label: 'Checkout steps',
      child: Row(
        children: [
          for (var i = 0; i < labels.length; i++) ...[
            if (i > 0)
              Expanded(
                child: Divider(
                  color: done[i - 1] ? s.primary : s.border,
                  thickness: 2,
                ),
              ),
            Column(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor: done[i] ? s.primary : s.muted,
                  child: done[i]
                      ? Icon(Icons.check, size: 16, color: s.onPrimary)
                      : Text(
                          '${i + 1}',
                          style: TextStyle(color: s.text, fontSize: 12),
                        ),
                ),
                const SizedBox(height: 2),
                Text(
                  labels[i],
                  style: TextStyle(fontSize: TypeScale.caption, color: s.text),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
