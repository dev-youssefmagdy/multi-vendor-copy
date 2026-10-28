import 'package:flutter_test/flutter_test.dart';
import 'package:application/core/errors/app_exception.dart';
import 'package:application/features/deeplink/url_normalizer.dart';

void main() {
  const n = UrlNormalizer();
  test('normalizeHost', () {
    expect(n.normalizeHost(' HTTPS://Shop.Example.com/ '), 'shop.example.com');
    expect(n.normalizeHost('shop.example.com:8080/a?b=1'), 'shop.example.com');
    expect(n.normalizeHost('shop.example.com.'), 'shop.example.com');
    expect(() => n.normalizeHost('not a host'),
        throwsA(isA<InvalidStoreInputException>()));
    expect(() => n.normalizeHost('localhost'),
        throwsA(isA<InvalidStoreInputException>()));
    expect(() => n.normalizeHost(''), throwsA(isA<InvalidStoreInputException>()));
  });

  test('classifyInput code vs host', () {
    expect(n.classifyInput('myshop').isCode, isTrue);
    expect(n.classifyInput('my.shop.com').isCode, isFalse);
    expect(() => n.classifyInput('bad code!'),
        throwsA(isA<InvalidStoreInputException>()));
  });

  test('parseUri routes', () {
    DeepLinkTarget? p(String u) => n.parseUri(Uri.parse(u));
    expect(p('https://s.example.com/')!.kind, DeepLinkKind.home);
    expect(p('https://s.example.com')!.location, '/home');
    final prod = p('https://S.example.com/product/red%20shirt/?ref=x&q=a%20b')!;
    expect(prod.kind, DeepLinkKind.product);
    expect(prod.id, 'red shirt');
    expect(prod.host, 's.example.com');
    expect(prod.location, '/product/red%20shirt?ref=x&q=a+b');
    expect(p('https://s.example.com/products/abc')!.kind, DeepLinkKind.product);
    expect(p('https://s.example.com/category/shoes/')!.id, 'shoes');
    expect(p('https://s.example.com/cart/')!.location, '/cart');
    expect(p('https://s.example.com/checkout')!.kind, DeepLinkKind.checkout);
    expect(p('https://s.example.com/account')!.kind, DeepLinkKind.account);
    expect(p('https://s.example.com/unknown/x')!.kind, DeepLinkKind.home);
    expect(p('https://s.example.com/product')!.kind, DeepLinkKind.home);
  });

  test('store query handoff wins and is stripped; bad host -> null', () {
    final t = n.parseUri(Uri.parse(
        'https://landing.platform.com/product/9?store=Custom.Shop.com&api_base=http://evil'))!;
    expect(t.host, 'custom.shop.com');
    expect(t.query.containsKey('store'), isFalse);
    expect(n.parseUri(Uri.parse('myapp:///cart')), isNull);
  });
}
