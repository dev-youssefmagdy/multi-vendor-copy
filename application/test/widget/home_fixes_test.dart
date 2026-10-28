import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/features/home/home_providers.dart';
import 'package:application/themes/shared/sections.dart';
import 'package:application/themes/shared/skin.dart';
import '../support/home_fixtures.dart';

Widget wrap(Widget child, List<String> opened) => ProviderScope(
      overrides: [homeRepositoryProvider.overrideWithValue(FakeHomeRepository())],
      child: MaterialApp(
        home: SkinScope(
          skin: ThemeSkin.resolve('elora', 'v2', const {}),
          onOpen: (_, r) => opened.add(r),
          child: Scaffold(body: SingleChildScrollView(child: child)),
        ),
      ),
    );

void main() {
  testWidgets('see_all link opens the category route', (t) async {
    final opened = <String>[];
    await t.pumpWidget(wrap(
        ProductRail({...payloadFor('new_arrivals'), 'key': 'new_arrivals', 'see_all': 'new-in'}),
        opened));
    await t.tap(find.text('See all'));
    expect(opened, ['/category/new-in']);
  });

  testWidgets('hero autoplays to the next slide', (t) async {
    await t.pumpWidget(wrap(HeroBannerCarousel({...payloadFor('hero_banner')}), []));
    expect(find.text('Big Sale'), findsOneWidget);
    await t.pump(const Duration(seconds: 5));
    await t.pump(const Duration(milliseconds: 500));
    final pager = t.widget<PageView>(find.byKey(const Key('hero_pager')));
    expect(pager.controller!.page, 1.0);
    await t.pumpWidget(const SizedBox());
  });
}
