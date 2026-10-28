import 'package:flutter_test/flutter_test.dart';
import 'package:application/core/storage/key_value_store.dart';
import 'package:application/features/auth/token_vault.dart';
import 'package:application/features/tenant/tenant_models.dart';
import 'package:application/features/tenant/tenant_storage_service.dart';
import '../support/harness.dart';

void main() {
  test('storage namespaces per tenant and wipes only one', () async {
    final h = await Harness.create();
    final st = TenantStorageService(h.prefs, h.secure);
    await st.saveActiveTenant(shopA);
    await st.saveConfig('a1', const TenantConfig(appName: 'A'), 'e1');
    await st.saveActiveTenant(shopB);
    await st.saveConfig('b2', const TenantConfig(appName: 'B'), 'e2');
    expect(st.activeTenantId, 'b2');
    expect(st.loadConfig('a1')!.appName, 'A');
    expect(st.loadConfig('b2')!.appName, 'B');
    expect(st.loadEtag('a1'), 'e1');
    expect(st.loadRecent().map((t) => t.id), ['b2', 'a1']);
    expect(h.prefs.getKeys().where((k) => k.startsWith('tenant:a1:')), isNotEmpty);

    await TokenVault(h.secure).save('a1', access: 'tokA', refresh: 'rA');
    await st.wipeTenant('a1');
    expect(st.loadConfig('a1'), isNull);
    expect(st.loadConfig('b2'), isNotNull);
    expect(await TokenVault(h.secure).readAccess('a1'), isNull);
    expect(st.loadRecent().map((t) => t.id), ['b2']);
  });

  test('token vault isolates tenants', () async {
    final s = InMemorySecureStore();
    final v = TokenVault(s);
    await v.save('a1', access: 'A', refresh: 'RA');
    await v.save('b2', access: 'B');
    expect(await v.readAccess('a1'), 'A');
    expect(await v.readAccess('b2'), 'B');
    expect(await v.readRefresh('b2'), isNull);
    await v.clear('a1');
    expect(await v.readAccess('a1'), isNull);
    expect(await v.readAccess('b2'), 'B');
    expect(s.data.keys.every((k) => k.startsWith('tenant:')), isTrue);
  });

  test('recents are capped', () async {
    final h = await Harness.create();
    final st = TenantStorageService(h.prefs, h.secure);
    for (var i = 0; i < 12; i++) {
      await st.saveActiveTenant(Tenant(id: '$i', domain: 'd$i.com', name: 'n$i'));
    }
    expect(st.loadRecent().length, TenantStorageService.maxRecent);
    expect(st.loadRecent().first.id, '11');
  });
}
