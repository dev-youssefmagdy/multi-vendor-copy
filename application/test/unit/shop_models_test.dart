import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/core/errors/app_exception.dart';
import 'package:application/core/l10n/strings.dart';
import 'package:application/core/network/api_client.dart';
import 'package:application/core/platform/html_text.dart';
import 'package:application/features/shop/commerce_models.dart';
import 'package:application/features/shop/screens/listing_screen.dart';
import 'package:application/features/shop/widgets/shop_widgets.dart';
import 'package:application/themes/shared/skin.dart';

void main() {
  test('ApiClient.mapError keeps code + data of a 409 price_changed', () {
    final e = ApiClient.mapError(DioException(
        requestOptions: RequestOptions(path: '/checkout'),
        response: Response(
            requestOptions: RequestOptions(path: '/checkout'),
            statusCode: 409,
            data: {
              'success': false,
              'code': 'price_changed',
              'message': 'Prices have changed.',
              'data': {'grand_total': 25}
            })));
    expect(e, isA<ApiException>());
    final a = e as ApiException;
    expect(a.code, 'price_changed');
    expect(a.statusCode, 409);
    expect(CartData.fromJson(Map<String, dynamic>.from(a.data as Map)).grandTotal, 25);
  });

  test('401 with a code (invalid_credentials) is a normal API error, bare 401 is Unauthorized', () {
    DioException d(Object? data) => DioException(
        requestOptions: RequestOptions(path: '/x'),
        response: Response(requestOptions: RequestOptions(path: '/x'), statusCode: 401, data: data));
    expect(ApiClient.mapError(d({'code': 'invalid_credentials', 'message': 'Invalid email or password.'})),
        isA<ApiException>());
    expect(ApiClient.mapError(d(null)), isA<UnauthorizedException>());
  });

  test('CartData parses the server cart/quote shape', () {
    final c = CartData.fromJson({
      'items': [
        {'key': 'k', 'slug': 's', 'name': 'N', 'qty': 2, 'unit_price': 5.5, 'line_total': 11, 'out_of_stock': true}
      ],
      'count': 2,
      'items_total': 11,
      'tax': 1.1,
      'grand_total': 17.1,
      'coupon': {'code': 'X', 'discount': 2, 'valid': false, 'error': 'minimum_spend_not_met'},
      'shipping': {'available': true, 'amount': 5},
      'free_shipping': {'progress_pct': 40},
      'has_unavailable': true,
    });
    expect(c.items.single.outOfStock, isTrue);
    expect(c.couponValid, isFalse);
    expect(c.shipping!.amount, 5);
    expect(c.freeShippingPct, 40);
    expect(c.hasUnavailable, isTrue);
  });

  test('queryForSlug maps list screens', () {
    expect(queryForSlug('offers').kind, ListingKind.offers);
    expect(queryForSlug('full-star').slug, 'top-rated');
    expect(queryForSlug('new-in').slug, 'new-in');
    expect(queryForSlug('shoes').kind, ListingKind.category);
    expect(queryForSlug('all-products').kind, ListingKind.all);
  });

  test('ListingQuery value equality + params', () {
    const a = ListingQuery(ListingKind.category, slug: 's', sort: 'ascending', min: 1);
    expect(a, const ListingQuery(ListingKind.category, slug: 's', sort: 'ascending', min: 1));
    expect(a.hashCode, const ListingQuery(ListingKind.category, slug: 's', sort: 'ascending', min: 1).hashCode);
    expect(a.params, {'sort': 'ascending', 'min': 1});
  });

  test('htmlToText strips tags and decodes entities', () {
    expect(htmlToText('<p>A&nbsp;&amp;<br>B</p><script>x()</script><ul><li>one</li></ul>'), 'A &\nB\n• one');
  });

  test('translate falls back to English and fills args', () {
    expect(translate('ar', 'Cart'), 'السلة');
    expect(translate('fr', 'Cart'), 'Cart');
    expect(translate('en', 'Hi :name', {'name': 'Sam'}), 'Hi Sam');
    expect(translate('ar', 'Untranslated'), 'Untranslated');
  });

  testWidgets('SkinButton follows the theme x variant skin', (t) async {
    Future<Color?> bg(String theme, String v) async {
      await t.pumpWidget(MaterialApp(
          home: SkinScope(
              skin: ThemeSkin.resolve(theme, v, const {}),
              child: Scaffold(body: SkinButton(label: 'Go', onPressed: () {})))));
      final b = t.widget<FilledButton>(find.byType(FilledButton));
      return b.style!.backgroundColor!.resolve({});
    }

    final souqifyV2 = await bg('souqify', 'v2');
    final eloraV5 = await bg('elora', 'v5');
    expect(souqifyV2, const Color(0xFF8B03BD));
    expect(eloraV5, const Color(0xFF132092));
  });
}
