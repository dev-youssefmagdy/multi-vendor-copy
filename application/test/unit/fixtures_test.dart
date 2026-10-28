import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/features/startup/version.dart';
import 'package:application/features/tenant/tenant_models.dart';

Map<String, dynamic> fx(String n) =>
    jsonDecode(File('test/fixtures/$n.json').readAsStringSync())
        as Map<String, dynamic>;

void main() {
  test('resolve fixtures', () {
    final a = ResolveResult.fromJson(fx('resolve_active')['data']);
    expect(a.status, TenantStatus.active);
    expect(a.tenant!.id, 'acme');
    expect(ResolveResult.fromJson(fx('resolve_suspended')['data']).status,
        TenantStatus.suspended);
    final nf = ResolveResult.fromJson(fx('resolve_not_found')['data']);
    expect(nf.status, TenantStatus.notFound);
    expect(nf.tenant, isNull);
  });

  test('bootstrap fixture parses and round-trips', () {
    final c = TenantConfig.fromJson(fx('bootstrap')['data']);
    expect(c.themeSlug, 'elora');
    expect(c.variantKey, 'v2');
    expect(c.colors['bg_main'], '#FFFFFF');
    expect(c.sections.map((s) => s.key), ['hero', 'categories']);
    expect(c.sections.first.widget, 'hero_slider');
    expect(c.allowedSectionKeys['elora']!['v2'], contains('new_arrivals'));
    expect(c.logoUrl, contains('logo.png'));
    expect(c.displayName, 'Acme App');
    expect(c.locales[1].rtl, isTrue);
    expect(c.currencies.first.isDefault, isTrue);
    expect(c.currencies[1].rate, 0.92);
    expect(c.feature('favorites'), isTrue);
    expect(c.feature('guest_cart'), isFalse);
    expect(c.maintenance, isFalse);
    expect(c.configVersion, 'a1b2c3d4e5f60718');
    final r = TenantConfig.fromJson(c.toJson());
    expect(r.sections.length, 2);
    expect(r.themeSlug, 'elora');
    expect(r.currencies.length, 2);
    expect(r.minAppVersion, '1.0.0');
  });

  test('compareVersions', () {
    expect(compareVersions('1.0.0', '1.0.1') < 0, isTrue);
    expect(compareVersions('1.10', '1.9') > 0, isTrue);
    expect(compareVersions('2.0', '2.0.0'), 0);
  });
}
