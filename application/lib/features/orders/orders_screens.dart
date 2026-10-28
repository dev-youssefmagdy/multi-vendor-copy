import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../../core/platform/web_surface.dart';
import '../../themes/shared/skin.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/widgets/shop_widgets.dart';

String statusLabel(String? s) {
  if (s == null || s.isEmpty) return '';
  final w = s.replaceAll('_', ' ');
  return w[0].toUpperCase() + w.substring(1);
}

Color statusColor(ThemeSkin s, String? st) {
  switch (st) {
    case 'delivered' || 'completed':
      return s.success;
    case 'cancelled' || 'canceled' || 'failed' || 'refunded':
      return s.error;
    default:
      return s.primary;
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});
  final String? status;
  @override
  Widget build(BuildContext context) {
    final s = SkinScope.of(context);
    final c = statusColor(s, status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
      decoration: BoxDecoration(
          color: c.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(statusLabel(status),
          style: TextStyle(color: c, fontSize: 12, fontWeight: FontWeight.w700)),
    );
  }
}

class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});
  @override
  ConsumerState<OrdersScreen> createState() => _OrdersState();
}

class _OrdersState extends ConsumerState<OrdersScreen> {
  String _status = '';
  @override
  Widget build(BuildContext context) {
    final async = ref.watch(ordersProvider(''));
    return ShopScaffold(
      title: context.tr('My orders'),
      body: RequireAuth(
        from: '/orders',
        child: AsyncBody<List<OrderSummary>>(async,
            onRetry: () => ref.invalidate(ordersProvider('')), data: (all) {
          final statuses = {for (final o in all) if (o.status != null) o.status!}.toList();
          final list = _status.isEmpty ? all : all.where((o) => o.status == _status).toList();
          return Column(children: [
            if (statuses.length > 1)
              SizedBox(
                height: 52,
                child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.all(8), children: [
                  Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: ChoiceChip(
                          label: Text(context.tr('All')),
                          selected: _status.isEmpty,
                          onSelected: (_) => setState(() => _status = ''))),
                  for (final st in statuses)
                    Padding(
                        padding: const EdgeInsets.only(right: 6),
                        child: ChoiceChip(
                            key: Key('status_$st'),
                            label: Text(statusLabel(st)),
                            selected: _status == st,
                            onSelected: (_) => setState(() => _status = st))),
                ]),
              ),
            Expanded(
              child: list.isEmpty
                  ? EmptyView(context.tr('No orders yet'),
                      icon: Icons.receipt_long_outlined,
                      actionLabel: context.tr('Continue shopping'),
                      onAction: () => openRoute(context, '/home'))
                  : RefreshIndicator(
                      onRefresh: () async => ref.invalidate(ordersProvider('')),
                      child: ListView.separated(
                        itemCount: list.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final o = list[i];
                          return ListTile(
                            key: Key('order_${o.uuid}'),
                            title: Text('#${o.uuid.length > 8 ? o.uuid.substring(0, 8) : o.uuid}'),
                            subtitle: Text((o.createdAt ?? '').split('T').first),
                            trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Price(o.grandTotal, style: const TextStyle(fontWeight: FontWeight.w800)),
                                  StatusChip(o.status),
                                ]),
                            onTap: () => openRoute(context, '/orders/${o.uuid}'),
                          );
                        },
                      ),
                    ),
            ),
          ]);
        }),
      ),
    );
  }
}

class OrderDetailScreen extends ConsumerWidget {
  const OrderDetailScreen(this.uuid, {super.key, this.justPlaced = false});
  final String uuid;
  final bool justPlaced;
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Order'),
        body: RequireAuth(
            from: '/orders/$uuid',
            child: AsyncBody<OrderDetail>(ref.watch(orderProvider(uuid)),
                onRetry: () => ref.invalidate(orderProvider(uuid)),
                data: (o) => _OrderBody(o, justPlaced: justPlaced))),
      );
}

class _OrderBody extends ConsumerStatefulWidget {
  const _OrderBody(this.order, {required this.justPlaced});
  final OrderDetail order;
  final bool justPlaced;
  @override
  ConsumerState<_OrderBody> createState() => _OrderBodyState();
}

class _OrderBodyState extends ConsumerState<_OrderBody> {
  bool _busy = false;

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

  Future<void> _cancel() async {
    final repo = ref.read(ordersRepositoryProvider);
    final reasons = await repo.cancellationReasons();
    if (!mounted) return;
    String? reason = reasons.isNotEmpty ? '${reasons.first['value']}' : null;
    final note = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, set) => AlertDialog(
          title: Text(context.tr('Cancel order')),
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            RadioGroup<String>(
              groupValue: reason,
              onChanged: (v) => set(() => reason = v),
              child: Column(children: [
                for (final r in reasons)
                  RadioListTile<String>(
                      key: Key('reason_${r['value']}'),
                      value: '${r['value']}',
                      title: Text('${r['label']}')),
              ]),
            ),
            TextField(controller: note, decoration: InputDecoration(labelText: context.tr('Note'))),
          ]),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(context.tr('Back'))),
            TextButton(
                key: const Key('confirm_cancel'),
                onPressed: () => Navigator.pop(ctx, true),
                child: Text(context.tr('Cancel order'))),
          ],
        ),
      ),
    );
    final n = note.text;
    note.dispose();
    if (ok != true || reason == null) return;
    await _run(() async {
      await repo.cancel(widget.order.summary.uuid, reason: reason!, note: n);
      ref.invalidate(orderProvider(widget.order.summary.uuid));
      ref.invalidate(ordersProvider(''));
    });
  }

  @override
  Widget build(BuildContext context) {
    final o = widget.order;
    final sm = o.summary;
    final s = SkinScope.of(context);
    final status = sm.status;
    final cancelled = status == 'cancelled' || status == 'canceled';
    final canPay = !sm.paid && !cancelled && sm.paymentMethod != null && sm.paymentMethod != 'cod';
    final canReturn = !cancelled && (o.suggestReturn || status == 'delivered' || status == 'completed');
    final symbol = ref.watch(currencySymbolProvider);
    String m(String k) => formatPrice(symbol, o.num_(k));
    return ListView(padding: const EdgeInsets.all(16), children: [
      if (widget.justPlaced)
        Container(
          key: const Key('order_placed_banner'),
          padding: const EdgeInsets.all(12),
          margin: const EdgeInsets.only(bottom: 12),
          decoration: BoxDecoration(
              color: s.success.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(s.radius)),
          child: Text(context.tr('Your order has been placed.')),
        ),
      Row(children: [
        Expanded(
            child: Text('#${sm.uuid}',
                style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15))),
        StatusChip(status),
      ]),
      const SizedBox(height: 4),
      Text(sm.paid ? context.tr('Paid') : context.tr('Unpaid'),
          key: const Key('paid_label'), style: TextStyle(color: sm.paid ? s.success : s.subtitle)),
      const Divider(height: 24),
      for (final it in o.items)
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text('${it['product_name'] ?? ''}'),
          subtitle: Text('x${it['qty']}'),
          trailing: Price(it['grand_total'] as num?),
        ),
      const Divider(),
      _row(context.tr('Subtotal'), m('subtotal')),
      _row(context.tr('Shipping'), m('shipping_charge')),
      if ((o.num_('coupon_discount') ?? 0) > 0) _row(context.tr('Discount'), m('coupon_discount')),
      if ((o.num_('tax_amount') ?? 0) > 0) _row(context.tr('Tax'), m('tax_amount')),
      _row(context.tr('Total'), formatPrice(symbol, sm.grandTotal), bold: true),
      if (o.raw['shipping_address'] != null) ...[
        const Divider(height: 24),
        Text(context.tr('Shipping address'), style: const TextStyle(fontWeight: FontWeight.w800)),
        const SizedBox(height: 4),
        Text(_addr(o.raw['shipping_address'])),
      ],
      const SizedBox(height: 16),
      if (canPay)
        SkinButton(
            key: const Key('pay_now'),
            label: context.tr('Pay now'),
            onPressed: () => openRoute(context, '/payment/${sm.uuid}')),
      const SizedBox(height: 8),
      SkinButton(
          key: const Key('track_order'),
          outline: true,
          label: context.tr('Track order'),
          onPressed: () => openRoute(context, '/orders/${sm.uuid}/tracking')),
      const SizedBox(height: 8),
      SkinButton(
          key: const Key('view_invoice'),
          outline: true,
          label: context.tr('Invoice'),
          onPressed: () => openRoute(context, '/orders/${sm.uuid}/invoice')),
      const SizedBox(height: 8),
      SkinButton(
          key: const Key('reorder'),
          outline: true,
          busy: _busy,
          label: context.tr('Reorder'),
          onPressed: () {
            final nav = GoRouter.of(context);
            _run(() async {
              await ref.read(ordersRepositoryProvider).reorder(sm.uuid);
              ref.invalidate(cartProvider);
              nav.go('/cart');
            });
          }),
      if (canReturn) ...[
        const SizedBox(height: 8),
        SkinButton(
            key: const Key('request_return'),
            outline: true,
            label: context.tr('Request a return'),
            onPressed: () => openRoute(context, '/orders/${sm.uuid}/return')),
      ],
      if (o.canCancel) ...[
        const SizedBox(height: 8),
        SkinButton(key: const Key('cancel_order'), outline: true, label: context.tr('Cancel order'), onPressed: _busy ? null : _cancel),
      ] else if (o.cancelBlockedReason != null && !cancelled)
        Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(o.cancelBlockedReason!, style: TextStyle(color: s.subtitle, fontSize: 12))),
    ]);
  }

  String _addr(Object? a) {
    if (a is Map) {
      return a.values.where((e) => e != null && '$e'.isNotEmpty).join(', ');
    }
    return '$a';
  }

  Widget _row(String l, String v, {bool bold = false}) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(children: [
        Expanded(child: Text(l, style: TextStyle(fontWeight: bold ? FontWeight.w800 : null))),
        Text(v, style: TextStyle(fontWeight: bold ? FontWeight.w900 : FontWeight.w500)),
      ]));
}

/// S13: tracking timeline.
class TrackingScreen extends ConsumerWidget {
  const TrackingScreen(this.uuid, {super.key});
  final String uuid;
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Track order'),
        body: RequireAuth(
            from: '/orders/$uuid/tracking',
            child: AsyncBody<Tracking>(ref.watch(trackingProvider(uuid)),
                onRetry: () => ref.invalidate(trackingProvider(uuid)),
                data: (t) => Builder(builder: (context) {
                  final s = SkinScope.of(context);
                  return ListView(padding: const EdgeInsets.all(16), children: [
                    Row(children: [
                      Expanded(child: Text('#$uuid', style: const TextStyle(fontWeight: FontWeight.w800))),
                      StatusChip(t.status),
                    ]),
                    if (t.trackingNumber != null)
                      Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: SelectableText('${context.tr('Tracking number')}: ${t.trackingNumber}')),
                    const SizedBox(height: 16),
                    if (t.timeline.isEmpty)
                      Text(context.tr('No updates yet'), key: const Key('no_timeline')),
                    for (final e in t.timeline)
                      ListTile(
                        key: Key('event_${e.at}'),
                        leading: Icon(Icons.circle, size: 14, color: s.primary),
                        title: Text(e.title ?? statusLabel(e.status)),
                        subtitle: Text([if (e.description != null) e.description!, (e.at ?? '').replaceFirst('T', ' ').split('.').first].join('\n')),
                      ),
                  ]);
                }))),
      );
}

/// S16: invoice (server HTML rendered in the web surface).
class InvoiceScreen extends ConsumerStatefulWidget {
  const InvoiceScreen(this.uuid, {super.key});
  final String uuid;
  @override
  ConsumerState<InvoiceScreen> createState() => _InvoiceState();
}

class _InvoiceState extends ConsumerState<InvoiceScreen> {
  late Future<String> _html = ref.read(ordersRepositoryProvider).invoiceHtml(widget.uuid);
  @override
  Widget build(BuildContext context) {
    final web = ref.watch(webSurfaceBuilderProvider);
    return ShopScaffold(
      title: context.tr('Invoice'),
      body: RequireAuth(
        from: '/orders/${widget.uuid}/invoice',
        child: FutureBuilder<String>(
          future: _html,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) return const LoadingView();
            if (snap.hasError) {
              return ErrorView(snap.error!, onRetry: () => setState(() {
                    _html = ref.read(ordersRepositoryProvider).invoiceHtml(widget.uuid);
                  }));
            }
            return web(context, html: snap.data, intercept: (_) => false);
          },
        ),
      ),
    );
  }
}

/// Route helper for deep links like `.../orders/:uuid`.
void goOrder(BuildContext c, String uuid) => c.push('/orders/$uuid');
