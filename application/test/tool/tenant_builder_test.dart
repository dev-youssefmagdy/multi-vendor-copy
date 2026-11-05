import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

import '../../tool/tenant_builder/tenant_builder.dart';
import 'tenant_builder_fixture.dart';

void main() {
  late Fixture f;
  setUp(() => f = Fixture.create());
  tearDown(() => f.dispose());

  group('validator', () {
    test('a valid registry passes', () {
      final r = f.validate();
      expect(r.errors, isEmpty, reason: r.errors.join('\n'));
    });

    test('unknown and disabled tenants fail clearly', () {
      expect(f.validate(tenants: {'nope'}), hasError('unknown tenant "nope"'));
      f.addTenant(f.store('gamma'), enabled: false);
      expect(f.validate(tenants: {'gamma'}).ok, isTrue);
      expect(
        f.validate(tenants: {'gamma'}, requireEnabled: true),
        hasError('disabled'),
      );
    });

    test('malformed, reserved and duplicate ids fail', () {
      f.writeIndex(
        entries: [
          {'id': 'Bad_Id', 'enabled': true},
          {'id': 'main', 'enabled': true},
          {'id': 'testshop', 'enabled': true},
          {'id': 'alpha', 'enabled': true},
          {'id': 'alpha', 'enabled': true},
          {'id': 'beta', 'enabled': 'yes'},
        ],
      );
      final r = f.validate();
      expect(r, hasError('"Bad_Id" must match'));
      expect(r, hasError('"main" is reserved'));
      expect(r, hasError('"testshop" is reserved'));
      expect(r, hasError('duplicate tenant id "alpha"'));
      expect(r, hasError('tenants[5].enabled'));
    });

    test('manifest id must equal folder', () {
      f.writeManifest('beta', {...f.store('beta'), 'id': 'other'});
      expect(f.validate(), hasError('must equal the folder name "beta"'));
    });

    test('duplicate Android application ids are rejected', () {
      final b = f.store('beta');
      (b['android'] as Map)['applicationId'] = 'COM.alpha.shop';
      f.writeManifest('beta', b);
      expect(
        f.validate(),
        hasError(
          'android.applicationId "COM.alpha.shop" is already used by tenant "alpha"',
        ),
      );
    });

    test('android and iOS url schemes must match', () {
      final b = f.store('beta');
      (b['ios'] as Map)['urlScheme'] = 'betaios';
      f.writeManifest('beta', b);
      expect(
        f.validate(),
        hasError('"betaios" must equal android.urlScheme "betashop"'),
      );
    });

    test('duplicate iOS bundle ids, schemes and hosts are rejected', () {
      final b = f.store('beta');
      (b['ios'] as Map)['bundleId'] = 'com.alpha.shop';
      (b['android'] as Map)['urlScheme'] = 'alphashop';
      (b['store'] as Map)['host'] = 'alpha.acme.com';
      f.writeManifest('beta', b);
      final r = f.validate();
      expect(r, hasError('ios.bundleId "com.alpha.shop" is already used'));
      expect(r, hasError('android.urlScheme "alphashop" is already used'));
      expect(r, hasError('store host "alpha.acme.com" is already used'));
    });

    test(
      'invalid ids, schemes, hosts, colors and schema version are rejected',
      () {
        final b = f.store('beta');
        b['schemaVersion'] = 2;
        (b['android'] as Map)['applicationId'] = 'shop';
        (b['ios'] as Map)['bundleId'] = 'com..x';
        (b['android'] as Map)['urlScheme'] = 'https';
        (b['store'] as Map)['host'] = 'https://beta.acme.com/';
        (b['store'] as Map)['extraLinkHosts'] = ['*.beta.com'];
        (b['branding'] as Map)['adaptiveIconBackground'] = 'red';
        (b['ios'] as Map)['teamId'] = 'abc';
        f.writeManifest('beta', b);
        final r = f.validate();
        expect(r, hasError('schemaVersion: must be 1'));
        expect(r, hasError('"shop" is not a valid Android application id'));
        expect(r, hasError('"com..x" is not a valid iOS bundle id'));
        expect(r, hasError('"https" is reserved'));
        expect(
          r,
          hasError('store.host: "https://beta.acme.com/" is not a valid host'),
        );
        expect(r, hasError('"*.beta.com" is not a valid host'));
        expect(r, hasError('adaptiveIconBackground: must be a #RRGGBB color'));
        expect(r, hasError('ios.teamId'));
        f.writeIndex(domain: 'not a domain', schema: 3);
        expect(f.validate(), hasError('platformDomain'));
        expect(f.validate(), hasError('index.json: schemaVersion'));
      },
    );

    test('display name rules', () {
      f.writeManifest('beta', {
        ...f.store('beta'),
        'displayName': 'A <b> & co',
      });
      expect(f.validate(), hasError('displayName'));
      f.writeManifest('beta', {...f.store('beta'), 'displayName': 'x' * 31});
      expect(f.validate(), hasError('max 30 characters'));
    });

    test('missing / bad icons fail with the file path', () {
      File(f.path('tenants/beta/assets/app_icon.png')).deleteSync();
      expect(
        f.validate(),
        hasError('file not found: tenants/beta/assets/app_icon.png'),
      );
      f.png('tenants/beta/assets/app_icon.png', 512);
      expect(f.validate(), hasError('must be at least 1024x1024'));
      f.png('tenants/beta/assets/app_icon.png', 1024, alpha: true);
      expect(f.validate(), hasError('alpha channel'));
      // Android-only validation accepts transparency (warning only).
      expect(f.validate(platform: 'android').ok, isTrue);
      File(f.path('tenants/beta/assets/app_icon.png'))
          .writeAsStringSync('not a png');
      expect(f.validate(), hasError('is not a valid PNG'));
      final b = f.store('beta');
      (b['branding'] as Map)['appIcon'] = '../alpha/assets/app_icon.png';
      f.writeManifest('beta', b);
      expect(
        f.validate(),
        hasError('must be a relative path inside tenants/beta/'),
      );
      final c = f.store('beta');
      (c['branding'] as Map).remove('appIcon');
      f.writeManifest('beta', c);
      expect(f.validate(), hasError('branding.appIcon: required'));
    });

    test('push without Firebase config is rejected', () {
      f.writeManifest('beta', {
        ...f.store('beta'),
        'push': {'enabled': true},
      });
      expect(f.validate(), hasError('google-services.json'));
    });

    test('unknown fields, secrets in manifest and key files are rejected', () {
      final b = f.store('beta');
      (b['android'] as Map)['keystorePassword'] = 'hunter2';
      b['colour'] = '#fff';
      f.writeManifest('beta', b);
      f.file('tenants/beta/android/upload.jks', 'x');
      f.file(
        'tenants/beta/android/play.json',
        '{"type":"service_account","private_key":"-----BEGIN"}',
      );
      final r = f.validate();
      expect(r, hasError('colour: unknown field'));
      expect(r, hasError('android.keystorePassword: looks like a secret'));
      expect(r, hasError('upload.jks: signing/credential files'));
      expect(r, hasError('play.json: looks like a service-account'));
      // The secret value itself is never echoed.
      expect(r.issues.join('\n'), isNot(contains('hunter2')));
    });

    test('release gate: placeholders, signing and API base', () {
      final b = f.store('beta');
      (b['android'] as Map)['applicationId'] = 'com.example.beta';
      f.writeManifest('beta', b);
      expect(f.validate(tenants: {'beta'}).ok, isTrue); // warning only
      final r = f.validate(
        tenants: {'beta'},
        release: true,
        platform: 'android',
      );
      expect(r, hasError('placeholder'));
      expect(r, hasError('no upload key'));
      expect(r, hasError('PLATFORM_API_BASE'));

      final ks = f.path('beta.jks');
      File(ks).writeAsStringSync('k');
      f.writeManifest('beta', f.store('beta'));
      final env = {
        'ANDROID_SIGNING_TENANT': 'beta',
        'ANDROID_KEYSTORE_PATH': ks,
        'ANDROID_KEYSTORE_PASSWORD': 'x',
        'ANDROID_KEY_ALIAS': 'upload',
        'ANDROID_KEY_PASSWORD': 'x',
        'PLATFORM_API_BASE': 'https://api.acme.com',
      };
      final ok = f.validate(
        tenants: {'beta'},
        release: true,
        platform: 'android',
        env: env,
      );
      expect(ok.errors, isEmpty, reason: ok.errors.join('\n'));
      // Another tenant's key is never used for this tenant.
      final other = f.validate(
        tenants: {'alpha'},
        release: true,
        platform: 'android',
        env: env,
      );
      expect(other, hasError('no upload key'));
      // Per-tenant properties file works too.
      f.file(
        'android/keystores/alpha.properties',
        'storeFile=../../beta.jks\nstorePassword=x\nkeyAlias=upload\nkeyPassword=x\n',
      );
      expect(
        f
            .validate(
              tenants: {'alpha'},
              release: true,
              platform: 'android',
              env: env,
            )
            .ok,
        isTrue,
      );
    });
  });

  group('generator', () {
    ToolContext ctx() => ToolContext(
      ws: f.ws,
      log: Log(out: TestSink(), err: TestSink()),
      runner: DryRunProcessRunner((_) {}),
      env: const {},
    );

    test('android flavor files are idempotent and deterministic', () async {
      final reg = loadRegistry(f.ws).registry!;
      final a = AndroidTarget();
      final first = await a.generate(ctx(), reg.manifests['alpha']!);
      expect(
        first.written,
        contains('android/app/src/alpha/res/mipmap-xxxhdpi/ic_launcher.png'),
      );
      final bytes = File(
        f.path('android/app/src/alpha/res/mipmap-mdpi/ic_launcher.png'),
      ).readAsBytesSync();
      final second = await a.generate(ctx(), reg.manifests['alpha']!);
      expect(second.changed, isFalse);
      expect(
        File(f.path('android/app/src/alpha/res/mipmap-mdpi/ic_launcher.png'))
            .readAsBytesSync(),
        bytes,
      );
      final check = await a.generate(
        ctx(),
        reg.manifests['alpha']!,
        check: true,
      );
      expect(check.changed, isFalse);
    });

    test('adaptive icon + extra hosts, and stale files are removed', () async {
      final b = f.store('beta');
      (b['branding'] as Map)['appIconForeground'] = 'assets/fg.png';
      (b['store'] as Map)['extraLinkHosts'] = ['shop.beta.com'];
      f.writeManifest('beta', b);
      f.png('tenants/beta/assets/fg.png', 1024, alpha: true);
      final a = AndroidTarget();
      await a.generate(ctx(), loadRegistry(f.ws).registry!.manifests['beta']!);
      final manifest = File(f.path('android/app/src/beta/AndroidManifest.xml'))
          .readAsStringSync();
      expect(manifest, contains('android:host="shop.beta.com"'));
      expect(
        File(
          f.path('android/app/src/beta/res/mipmap-anydpi-v26/ic_launcher.xml'),
        ).existsSync(),
        isTrue,
      );

      f.writeManifest('beta', f.store('beta'));
      final r = await a.generate(
        ctx(),
        loadRegistry(f.ws).registry!.manifests['beta']!,
      );
      expect(r.deleted, contains('android/app/src/beta/AndroidManifest.xml'));
      expect(
        File(
          f.path('android/app/src/beta/res/mipmap-anydpi-v26/ic_launcher.xml'),
        ).existsSync(),
        isFalse,
      );
    });

    test('platform tenant keeps main/ icons (no flavor dir)', () async {
      await AndroidTarget().generate(
        ctx(),
        loadRegistry(f.ws).registry!.manifests['platform']!,
      );
      expect(
        Directory(f.path('android/app/src/platform')).existsSync(),
        isFalse,
      );
    });

    test('unmanaged native folders are never overwritten; prune only removes generated ones', () async {
      f.file('android/app/src/alpha/res/values/handmade.xml', '<resources/>');
      await expectLater(
        AndroidTarget().generate(
          ctx(),
          loadRegistry(f.ws).registry!.manifests['alpha']!,
        ),
        throwsA(isA<ToolException>()),
      );
      expect(
        File(f.path('android/app/src/alpha/res/values/handmade.xml'))
            .existsSync(),
        isTrue,
      );

      await AndroidTarget().generate(
        ctx(),
        loadRegistry(f.ws).registry!.manifests['beta']!,
      );
      final removed = AndroidTarget().prune(ctx(), {'alpha', 'platform'});
      expect(removed, ['android/app/src/beta']);
      expect(Directory(f.path('android/app/src/alpha')).existsSync(), isTrue);
      expect(Directory(f.path('android/app/src/main')).existsSync(), isTrue);
    });

    test('dart defines: tenant binding, sorted, no secrets', () {
      final reg = loadRegistry(f.ws).registry!;
      final d = dartDefinesFor(
        reg.manifests['alpha']!,
        appVersion: '2.3.4',
        apiBase: 'https://api.acme.com/',
      );
      expect(d, {
        'APP_URL_SCHEME': 'alphashop',
        'APP_VERSION': '2.3.4',
        'PLATFORM_API_BASE': 'https://api.acme.com',
        'TENANT_HOST': 'alpha.acme.com',
        'TENANT_ID': 'alpha',
        'TENANT_LINK_HOSTS': '',
        'TENANT_NAME': 'Alpha Store',
      });
      final p = dartDefinesFor(reg.manifests['platform']!, appVersion: '2.3.4');
      expect(p['TENANT_HOST'], '');
      expect(p['APP_URL_SCHEME'], 'storeapp');
      expect(
        encodeDefines(d).indexOf('APP_VERSION'),
        lessThan(encodeDefines(d).indexOf('TENANT_ID')),
      );
    });
  });

  group('cli', () {
    test('list --json for the CI matrix', () async {
      f.addTenant(f.store('gamma'), enabled: false);
      final (code, out) = await cli(f, [
        'list',
        '--json',
        '--enabled',
        '--platform',
        'android',
      ]);
      expect(code, 0);
      final rows = jsonDecode(out.trim()) as List;
      expect(rows.map((r) => r['id']), ['platform', 'alpha', 'beta']);
      expect(rows[1]['environment'], 'tenant-alpha-android');
    });

    test(
      'create copies the template, registers disabled, refuses to overwrite',
      () async {
        var (code, _) = await cli(f, ['create', '--tenant', 'gamma']);
        expect(code, 0);
        final m = jsonDecode(
          File(f.path('tenants/gamma/tenant.json')).readAsStringSync(),
        );
        expect(m['id'], 'gamma');
        expect(
          File(f.path('tenants/gamma/assets/app_icon.png')).existsSync(),
          isTrue,
        );
        final reg = loadRegistry(f.ws).registry!;
        expect(reg.entry('gamma')!.enabled, isFalse);
        // Template icon is flagged as a placeholder for release.
        expect(
          f.validate(tenants: {'gamma'}, release: true),
          hasError('still the _template icon'),
        );
        (code, _) = await cli(f, ['create', '--tenant', 'gamma']);
        expect(code, 1);
        (code, _) = await cli(f, ['create', '--tenant', 'Main']);
        expect(code, 1);
      },
    );

    test('validate exits non-zero on errors', () async {
      expect((await cli(f, ['validate', '--all'])).$1, 0);
      expect((await cli(f, ['validate', '--tenant', 'nope'])).$1, 1);
      expect((await cli(f, ['validate'])).$1, usageExit);
    });

    test(
      'build --dry-run: per-tenant flavor + define file, sequential summary',
      () async {
        final (code, out) = await cli(f, [
          'build',
          '--all',
          '--platform',
          'android',
          '--artifact',
          'aab',
          '--build-number',
          '77',
          '--dry-run',
        ]);
        expect(code, 0, reason: out);
        expect(
          out,
          contains(
            'appbundle --release --flavor alpha -t lib/main.dart '
            '--dart-define-from-file=build/tenant_config/alpha.json --build-name 2.3.4 --build-number 77',
          ),
        );
        expect(out, contains('--flavor beta'));
        expect(out, contains('dist/alpha/android/alpha-2.3.4+77.aab'));
        expect(
          File(f.path('build/tenant_config/beta.json')).existsSync(),
          isTrue,
        );
      },
    );

    test('release build without signing fails before building', () async {
      final (code, out) = await cli(f, [
        'build',
        '--tenant',
        'alpha',
        '--platform',
        'android',
      ]);
      expect(code, 1);
      expect(out, contains('no upload key'));
      expect(out, isNot(contains('[dry-run]')));
    });

    test('iOS builds are App Store IPAs only (no debug ipa)', () async {
      final (code, out) = await cli(f, [
        'build',
        '--tenant',
        'alpha',
        '--platform',
        'ios',
        '--mode',
        'debug',
        '--dry-run',
      ]);
      expect(code, 1);
      expect(out, contains('--mode release only'));
    });

    test('upload never targets production', () async {
      final (code, out) = await cli(f, [
        'upload',
        '--tenant',
        'alpha',
        '--platform',
        'android',
        '--track',
        'production',
        '--dry-run',
      ]);
      expect(code, 1);
      expect(out, contains('not allowed'));
    });

    test('generate --check detects drift', () async {
      expect((await cli(f, ['generate', '--all'])).$1, 0);
      expect((await cli(f, ['generate', '--all', '--check'])).$1, 0);
      f.png('tenants/alpha/assets/app_icon.png', 1024, color: [200, 1, 1]);
      expect((await cli(f, ['generate', '--all', '--check'])).$1, 1);
    });
  });
}
