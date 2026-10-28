import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import 'package:application/design/design.dart';
import 'package:application/features/theme/theme_mode.dart';
import 'package:application/themes/shared/skin.dart';
import '../support/mocks.dart';

void main() {
  test('every variant has an AA-contrast dark skin', () {
    for (final (theme, variants) in [
      ('ecommet', ['v1']),
      ('elora', ['v1', 'v2', 'v3', 'v4', 'v5', 'v6']),
      ('souqify', ['v1', 'v2', 'v3', 'v4', 'v5', 'v6']),
    ]) {
      for (final v in variants) {
        final d = ThemeSkin.resolve(theme, v, const {}).toDark();
        expect(d.isDark, isTrue, reason: '$theme $v');
        expect(Contrast.ratio(d.text, d.background), greaterThanOrEqualTo(4.5));
        expect(Contrast.ratio(d.primary, d.background), greaterThanOrEqualTo(4.5), reason: '$theme $v primary');
        expect(Contrast.ratio(d.subtitleAA, d.background), greaterThanOrEqualTo(4.5));
      }
    }
  });

  testWidgets('appearance toggle persists per device and darkens screens', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/account');
    await t.scrollUntilVisible(find.byKey(const Key('theme_mode_toggle')), 200, scrollable: find.byType(Scrollable).first);
    await t.tap(find.text('Dark'));
    await t.pumpAndSettle();
    expect(s.c.read(themeModeProvider), ThemeMode.dark);
    expect(s.c.read(sharedPreferencesProvider).getString('device.theme_mode'), 'dark');
    final scaffold = t.widget<Scaffold>(find.byType(Scaffold).last);
    expect(scaffold.backgroundColor, const Color(0xFF121212));
  });
}
