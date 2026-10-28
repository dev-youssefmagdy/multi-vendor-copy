import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/core/logger/app_logger.dart';
import 'package:application/features/tenant/tenant_models.dart';
import 'package:application/features/theme/theme_registry.dart';
import 'package:application/features/theme/theme_tokens.dart';

void main() {
  test('Tenant/ResolveResult parsing', () {
    final r = ResolveResult.fromJson({
      'tenant_id': 7,
      'domain': 'Shop.Example.COM',
      'name': 'S',
      'status': 'active',
      'api_base': 'https://x'
    });
    expect(r.status, TenantStatus.active);
    expect(r.tenant!.id, '7');
    expect(r.tenant!.domain, 'shop.example.com');
    expect(Tenant.fromJson(r.tenant!.toJson()).id, '7');
    expect(ResolveResult.fromJson({'status': 'not_found'}).tenant, isNull);
    expect(ResolveResult.fromJson({'status': 'suspended', 'tenant_id': 1}).status,
        TenantStatus.suspended);
  });

  test('TenantConfig tolerant parsing', () {
    final c = TenantConfig.fromJson({
      'settings': {'app_name': 'X', 'maintenance': true},
      'theme': {'slug': 'souqify', 'variant': 'v3'},
      'colors': {'primary': '#112233'},
      'features': ['wishlist'],
      'currencies': [
        {'code': 'USD'}
      ],
      'sections': [
        {'key': 'hero'}
      ],
      'config_version': 5,
    });
    expect(c.appName, 'X');
    expect(c.themeSlug, 'souqify');
    expect(c.variantKey, 'v3');
    expect(c.features['wishlist'], true);
    expect(c.currencies, ['USD']);
    expect(c.maintenance, isTrue);
    expect(c.configVersion, '5');
    final empty = TenantConfig.fromJson({});
    expect(empty.themeSlug, 'default');
  });

  test('Theme tokens: parsing and safe defaults', () {
    expect(ThemeTokens.parseColor('#f00'), const Color(0xFFFF0000));
    expect(ThemeTokens.parseColor('zzz'), isNull);
    final t = ThemeTokens.fromColors({'primary': 'nothex', 'secondary': '#00ff00'});
    expect(t.primary, ThemeTokens.defaults.primary);
    expect(t.secondary, const Color(0xFF00FF00));
    final reg = ThemeRegistry();
    expect(reg.resolve(null).definition.slug, 'default');
    final r = reg.resolve(const TenantConfig(
        themeSlug: 'elora', variantKey: 'v4', colors: {'primary': '#0000ff'}));
    expect(r.definition.slug, 'elora');
    expect(r.variantKey, 'v4');
    expect(r.tokens.primary, const Color(0xFF0000FF));
    expect(reg.resolve(const TenantConfig(themeSlug: 'custom-blade')).definition.slug,
        'default');
  });

  test('logger redacts secrets', () {
    final lines = <String>[];
    AppLogger(sink: lines.add)
      ..i('Authorization: Bearer abc.def.ghi')
      ..i('{"password":"hunter2","email":"a@b.c"}')
      ..i('token=xyz&x=1');
    final all = lines.join('\n');
    expect(all, isNot(contains('abc.def')));
    expect(all, isNot(contains('hunter2')));
    expect(all, isNot(contains('xyz')));
    expect(all, contains('a@b.c'));
  });
}
