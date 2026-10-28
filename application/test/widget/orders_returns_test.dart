import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import '../support/mocks.dart';

void main() {
  Future<Shop> boot(WidgetTester t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    return s;
  }

  testWidgets('orders list filters by status and opens the detail', (t) async {
    final s = await boot(t);
    await s.push(t, '/orders');
    expect(find.byKey(const Key('order_ord-1')), findsOneWidget);
    expect(find.byKey(const Key('order_ord-2')), findsOneWidget);
    await t.tap(find.byKey(const Key('status_delivered')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('order_ord-1')), findsNothing);
    await t.tap(find.byKey(const Key('order_ord-2')));
    await t.pumpAndSettle();
    expect(find.text('#ord-2'), findsOneWidget);
    expect(find.byKey(const Key('request_return')), findsOneWidget);
    expect(find.byKey(const Key('cancel_order')), findsNothing);
  });

  testWidgets('unpaid online order offers Pay now; cancel flow with reasons', (t) async {
    final s = await boot(t);
    s.orders.list = const [];
    await s.push(t, '/orders/ord-1');
    expect(find.byKey(const Key('paid_label')), findsOneWidget);
    expect(find.text('Unpaid'), findsOneWidget);
    await t.ensureVisible(find.byKey(const Key('cancel_order')));
    await t.tap(find.byKey(const Key('cancel_order')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('confirm_cancel')));
    await t.pumpAndSettle();
    expect(s.orders.cancelled, ['ord-1:changed_mind']);
  });

  testWidgets('tracking timeline and invoice (HTML in web surface)', (t) async {
    final s = await boot(t);
    await s.push(t, '/orders/ord-1/tracking');
    expect(find.text('TRK123', findRichText: true), findsNothing);
    expect(find.textContaining('TRK123'), findsOneWidget);
    expect(find.text('Shipped'), findsWidgets);
    await s.push(t, '/orders/ord-1/invoice');
    expect(s.webCalls.last, contains('Invoice ord-1'));
  });

  testWidgets('return request: multipart fields + photos, then detail', (t) async {
    final s = await boot(t);
    await s.push(t, '/orders/ord-2/return');
    await t.tap(find.byKey(const Key('return_item')));
    await t.pumpAndSettle();
    await t.tap(find.text('Product 1').last);
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('add_photos')));
    await t.pumpAndSettle();
    await t.ensureVisible(find.byKey(const Key('return_submit')));
    await t.tap(find.byKey(const Key('return_submit')));
    await t.pumpAndSettle();
    final f = s.orders.returnsCreated.single;
    expect(f['order_item_id'], 9);
    expect(f['reason'], 'damaged');
    expect(f['return_method'], 'pickup');
    expect(s.orders.photosCounts, [1]);
    expect(find.text('We got it'), findsOneWidget);
  });

  testWidgets('returns list', (t) async {
    final s = await boot(t);
    await s.push(t, '/returns');
    expect(find.byKey(const Key('return_5')), findsOneWidget);
  });
}
