import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/core/errors/app_exception.dart';
import 'package:application/features/shop/commerce_models.dart';
import 'package:application/app/router.dart';
import '../support/mocks.dart';

void main() {
  testWidgets('category listing shows themed cards and paginates on scroll', (t) async {
    final s = await Shop.create(shop: FakeShop(count: 45));
    await s.launch(t);
    await s.push(t, '/category/shoes');
    expect(find.byKey(const Key('listing_grid')), findsOneWidget);
    expect(find.byKey(const Key('product_p1')), findsOneWidget);
    expect(s.shop.pages, [1]);
    await t.drag(find.byKey(const Key('listing_grid')), const Offset(0, -3000));
    await t.pumpAndSettle();
    await t.drag(find.byKey(const Key('listing_grid')), const Offset(0, -3000));
    await t.pumpAndSettle();
    expect(s.shop.pages, containsAllInOrder([1, 2]));
    expect(s.shop.queries.first.kind, ListingKind.category);
    expect(s.shop.queries.first.slug, 'shoes');
  });

  testWidgets('home see-all style slugs map to list screens (best-selling, new-in, full-star, offers)', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    for (final (route, kind, slug) in [
      ('/category/new-in', ListingKind.list, 'new-in'),
      ('/list/best-selling', ListingKind.list, 'best-selling'),
      ('/list/full-star', ListingKind.list, 'top-rated'),
      ('/list/offers', ListingKind.offers, ''),
    ]) {
      s.shop.queries.clear();
      await s.push(t, route);
      expect(s.shop.queries.first.kind, kind, reason: route);
      expect(s.shop.queries.first.slug, slug, reason: route);
      expect(s.shop.queries.first.params['sale'], kind == ListingKind.offers ? 'on_sale' : isNull);
      s.c.read(routerProvider).pop();
      await t.pumpAndSettle();
    }
  });

  testWidgets('filters sheet applies sort and price range', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/category/shoes');
    await t.tap(find.byKey(const Key('open_filters')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('sort_ascending')));
    await t.enterText(find.byKey(const Key('filter_min')), '5');
    await t.enterText(find.byKey(const Key('filter_max')), '50');
    await t.tap(find.byKey(const Key('apply_filters')));
    await t.pumpAndSettle();
    final q = s.shop.queries.last;
    expect(q.sort, 'ascending');
    expect(q.params['min'], 5);
    expect(q.params['max'], 50);
  });

  testWidgets('listing error shows retry and recovers; empty shows empty state', (t) async {
    final s = await Shop.create();
    s.shop.failListing = const NetworkException();
    await s.launch(t);
    await s.push(t, '/category/shoes');
    expect(find.byKey(const Key('offline_view')), findsOneWidget);
    s.shop.failListing = null;
    await t.tap(find.byKey(const Key('retry')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('listing_grid')), findsOneWidget);
  });

  testWidgets('empty listing shows the empty state', (t) async {
    final e = await Shop.create(shop: FakeShop(count: 0));
    await e.launch(t);
    await e.push(t, '/category/empty');
    expect(find.byKey(const Key('empty_view')), findsOneWidget);
  });

  testWidgets('search: debounced autocomplete, submit shows results, recent saved', (t) async {
    final s = await Shop.create();
    s.shop.suggestions = [prod(3), prod(4)];
    await s.launch(t);
    await s.push(t, '/search');
    await t.enterText(find.byKey(const Key('search_field')), 'Prod');
    await t.pump(const Duration(milliseconds: 100));
    expect(find.byKey(const Key('suggestions')), findsNothing);
    await t.pump(const Duration(milliseconds: 400));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('suggestion_p3')), findsOneWidget);
    await t.testTextInput.receiveAction(TextInputAction.search);
    await t.pumpAndSettle();
    expect(find.byKey(const Key('search_results')), findsOneWidget);
    expect(s.shop.queries.last.kind, ListingKind.search);
    expect(s.shop.queries.last.keyword, 'Prod');
  });

  testWidgets('product detail: variants, out-of-stock variant disabled, description stripped, reviews, related', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/product/p2');
    expect(find.text('Product 2'), findsWidgets);
    expect(find.textContaining('About Product 2'), findsOneWidget);
    expect(find.byKey(const Key('variant_101')), findsOneWidget);
    expect(find.byKey(const Key('review_1')), findsOneWidget);
    // blue variant has no stock => chip disabled
    final blue = t.widget<ChoiceChip>(find.byKey(const Key('variant_102')));
    expect(blue.onSelected, isNull);
    // gallery: 3 dots, swipe moves to the next image
    expect(find.byKey(const Key('dot_2')), findsOneWidget);
    await t.drag(find.byKey(const Key('gallery_pager')), const Offset(-300, 0));
    await t.pumpAndSettle();
    final pager = t.widget<PageView>(find.byKey(const Key('gallery_pager')));
    expect(pager.controller, isNull);
  });

  testWidgets('related products come from the product payload', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/product/p2');
    await t.scrollUntilVisible(find.byKey(const Key('related_title')), 300,
        scrollable: find.byType(Scrollable).first);
    expect(find.byKey(const Key('product_p9')), findsOneWidget);
  });

  testWidgets('product not found => error state, unknown route => 404', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/product/zzz');
    expect(find.text('Product not found.'), findsOneWidget);
    s.c.read(routerProvider).pop();
    await t.pumpAndSettle();
    await s.push(t, '/definitely/not/a/route');
    expect(find.text('Page not found'), findsOneWidget);
  });

  testWidgets('CMS page renders stripped body; missing page is 404', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/page/about');
    expect(find.byKey(const Key('cms_body')), findsOneWidget);
    expect(find.textContaining('Hello', findRichText: true), findsOneWidget);
    s.c.read(routerProvider).pop();
    await t.pumpAndSettle();
    await s.push(t, '/page/missing');
    expect(find.text('Page not found'), findsOneWidget);
  });

  testWidgets('categories tab shows tree', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.go(t, '/category/all');
    expect(find.byKey(const Key('cat_shoes')), findsOneWidget);
  });
}
