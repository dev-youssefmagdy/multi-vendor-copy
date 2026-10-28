import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/features/deeplink/deep_link_service.dart';
import 'package:application/features/notifications/push_registration.dart';
import 'package:application/core/network/api_client.dart';
import 'package:application/features/shop/commerce_models.dart';
import 'package:application/app/providers.dart';
import '../support/mocks.dart';

class _Src implements PushTokenSource {
  @override
  Future<String?> currentToken() async => 'tok';
}

void main() {
  test('payment return link maps to result route, ignores http and no order', () {
    String? f(String u) => DeepLinkService.paymentLocationFor(Uri.parse(u));
    expect(f('storeapp://payment/result?order=u1&status=paid'),
        '/payment/result?order=u1&status=paid');
    expect(f('storeapp://payment/result?order=u1'), '/payment/result?order=u1');
    expect(f('storeapp://payment/result'), isNull);
    expect(f('https://payment/result?order=u1'), isNull);
    expect(f('storeapp://shop.example.com/cart'), isNull);
  });

  testWidgets('payment link arriving outside the WebView opens the result screen',
      (t) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.statuses = [PaymentStatus('ord-1', true, 'processing')];
    await s.launch(t);
    (s.c.read(deepLinkSourceProvider) as FakeDeepLinkSource).emit(Uri.parse('storeapp://payment/result?order=ord-1&status=paid'));
    await t.pumpAndSettle(const Duration(milliseconds: 50));
    expect(find.byKey(const Key('payment_paid')), findsOneWidget);
    expect(s.checkout.statusCalls, greaterThan(0));
  });

  test('push registration is a no-op while the flag is off', () async {
    final r = PushRegistration(_NoApi(), _Src(), enabled: false);
    expect(await r.register(), isFalse);
    expect(await r.unregister(), isFalse);
    expect(await PushRegistration(_NoApi(), const NoPushTokenSource(), enabled: true).register(),
        isFalse);
  });
}

class _NoApi implements ApiClient {
  @override
  dynamic noSuchMethod(Invocation i) => throw StateError('API must not be called');
}
