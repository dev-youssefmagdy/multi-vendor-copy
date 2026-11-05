import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:archive/archive.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:image/image.dart' as img;
import 'package:path/path.dart' as p;
import 'package:yaml/yaml.dart';

import '../../tool/tenant_builder/tenant_builder.dart';
import 'tenant_builder_fixture.dart';

// ------------------------------------------------------------ helpers ----

/// Minimal binary plist writer (test-only) so synthetic IPAs look like Xcode output.
Uint8List encodeBinaryPlist(Object root) {
  final objs = <Object?>[];
  final kids = <int, List<int>>{};
  int add(Object? o) {
    final i = objs.length;
    objs.add(o);
    if (o is Map) {
      final ks = [for (final k in o.keys) add('$k')];
      final vs = [for (final v in o.values) add(v)];
      kids[i] = [...ks, ...vs];
    } else if (o is List && o is! Uint8List) {
      kids[i] = [for (final e in o) add(e)];
    }
    return i;
  }

  add(root);
  final refSize = objs.length < 256 ? 1 : 2;
  final out = BytesBuilder()..add(ascii.encode('bplist00'));
  final offsets = <int>[];
  void len(int type, int n) {
    if (n < 15) {
      out.addByte((type << 4) | n);
    } else {
      out
        ..addByte((type << 4) | 0xF)
        ..addByte(0x11)
        ..add([n >> 8, n & 0xFF]);
    }
  }

  void ref(int r) => out.add(refSize == 1 ? [r] : [r >> 8, r & 0xFF]);
  for (var i = 0; i < objs.length; i++) {
    offsets.add(out.length);
    final o = objs[i];
    switch (o) {
      case bool():
        out.addByte(o ? 0x09 : 0x08);
      case int():
        final b = ByteData(8)..setInt64(0, o);
        out
          ..addByte(0x13)
          ..add(b.buffer.asUint8List());
      case Uint8List():
        len(0x4, o.length);
        out.add(o);
      case String():
        if (o.codeUnits.every((c) => c < 128)) {
          len(0x5, o.length);
          out.add(ascii.encode(o));
        } else {
          len(0x6, o.length);
          for (final u in o.codeUnits) {
            out.add([u >> 8, u & 0xFF]);
          }
        }
      case Map():
        len(0xD, o.length);
        kids[i]!.forEach(ref);
      case List():
        len(0xA, o.length);
        kids[i]!.forEach(ref);
      default:
        throw ArgumentError('unsupported $o');
    }
  }
  final tableOffset = out.length;
  for (final off in offsets) {
    out.add([off >> 8, off & 0xFF]);
  }
  final trailer = ByteData(32)
    ..setUint8(6, 2)
    ..setUint8(7, refSize)
    ..setUint64(8, objs.length)
    ..setUint64(16, 0)
    ..setUint64(24, tableOffset);
  out.add(trailer.buffer.asUint8List());
  return out.takeBytes();
}

/// Code-signature entitlements blob inside a fake executable.
Uint8List fakeExecutable(
  Map<String, Object?> entitlements, {
  String extra = '',
}) {
  final xml = utf8.encode(encodeXmlPlist(entitlements));
  final header = ByteData(8)
    ..setUint32(0, 0xfade7171)
    ..setUint32(4, xml.length + 8);
  return Uint8List.fromList([
    ...latin1.encode('\xCF\xFA\xED\xFE fake mach-o $extra'),
    ...List.filled(64, 0),
    ...header.buffer.asUint8List(),
    ...xml,
    ...List.filled(32, 0),
  ]);
}

/// Xcode-style "CgBI" PNG (BGRA, raw deflate) of [image].
Uint8List cgbiPng(img.Image image) {
  final rgba = image.convert(numChannels: 4, format: img.Format.uint8);
  final raw = BytesBuilder();
  for (var y = 0; y < rgba.height; y++) {
    raw.addByte(0);
    for (var x = 0; x < rgba.width; x++) {
      final px = rgba.getPixel(x, y);
      raw.add([px.b.toInt(), px.g.toInt(), px.r.toInt(), px.a.toInt()]);
    }
  }
  final out = BytesBuilder()
    ..add([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]);
  void chunk(String type, List<int> data) {
    final t = ascii.encode(type);
    out
      ..add((ByteData(4)..setUint32(0, data.length)).buffer.asUint8List())
      ..add(t)
      ..add(data)
      ..add(
        (ByteData(
          4,
        )..setUint32(0, getCrc32([...t, ...data]))).buffer.asUint8List(),
      );
  }

  chunk('CgBI', [0x50, 0x00, 0x20, 0x06]);
  chunk('IHDR', [
    ...(ByteData(4)..setUint32(0, rgba.width)).buffer.asUint8List(),
    ...(ByteData(4)..setUint32(0, rgba.height)).buffer.asUint8List(),
    8,
    6,
    0,
    0,
    0,
  ]);
  chunk('IDAT', Deflate(raw.takeBytes()).getBytes());
  chunk('IEND', const []);
  return out.takeBytes();
}

const team = 'ABCDE12345';

Map<String, Object?> infoPlist({
  String bundleId = 'com.alpha.shop',
  String name = 'Alpha Store',
  String version = '2.3.4',
  String build = '57',
  List<String> schemes = const ['alphashop'],
}) => {
  'CFBundleIdentifier': bundleId,
  'CFBundleDisplayName': name,
  'CFBundleShortVersionString': version,
  'CFBundleVersion': build,
  'CFBundleExecutable': 'Runner',
  'CFBundleURLTypes': [
    {'CFBundleURLName': bundleId, 'CFBundleURLSchemes': schemes},
  ],
  'CFBundleIcons': {
    'CFBundlePrimaryIcon': {'CFBundleIconName': 'AppIcon'},
  },
  'ITSAppUsesNonExemptEncryption': false,
  'NSCameraUsageDescription': 'camera',
  'NSPhotoLibraryUsageDescription': 'photos',
};

Map<String, Object?> signedEntitlements({
  String appId = '$team.com.alpha.shop',
  List<String> domains = const [
    'applinks:alpha.acme.com',
    'webcredentials:alpha.acme.com',
  ],
  bool getTaskAllow = false,
  Map<String, Object?> extra = const {},
}) => {
  'application-identifier': appId,
  'com.apple.developer.team-identifier': appId.split('.').first,
  'get-task-allow': getTaskAllow,
  'com.apple.developer.associated-domains': domains,
  ...extra,
};

Uint8List profile({
  String appId = '$team.com.alpha.shop',
  DateTime? expires,
  bool devices = false,
}) {
  final xml = encodeXmlPlist({
    'Name': 'match AppStore ${appId.substring(appId.indexOf('.') + 1)}',
    'TeamIdentifier': [appId.split('.').first],
    'ExpirationDate': expires ?? DateTime.utc(2099),
    'Entitlements': {
      'application-identifier': appId,
      'com.apple.developer.team-identifier': appId.split('.').first,
      'get-task-allow': false,
      'com.apple.developer.associated-domains': '*',
      'keychain-access-groups': ['$team.*'],
    },
    if (devices) 'ProvisionedDevices': ['00008110-000000000000001E'],
  });
  return Uint8List.fromList([
    0x30,
    0x82,
    0x10,
    0x00,
    ...utf8.encode(xml),
    0xA0,
    0x00,
  ]);
}

/// A synthetic IPA as Xcode would export it (binary Info.plist, signed blob,
/// embedded profile, loose AppIcon PNG, Dart snapshot).
Uint8List syntheticIpa({
  Map<String, Object?>? info,
  Map<String, Object?>? entitlements,
  Uint8List? embeddedProfile,
  List<int>? iconPng,
  String dartSnapshot = 'TENANT_HOST=alpha.acme.com',
  Map<String, List<int>> extra = const {},
}) {
  final a = Archive();
  void add(String name, List<int> bytes) =>
      a.addFile(ArchiveFile.bytes('Payload/Runner.app/$name', bytes));
  add('Info.plist', encodeBinaryPlist(info ?? infoPlist()));
  add('Runner', fakeExecutable(entitlements ?? signedEntitlements()));
  add('embedded.mobileprovision', embeddedProfile ?? profile());
  if (iconPng != null) add('AppIcon60x60@2x.png', iconPng);
  add('Assets.car', [1, 2, 3]);
  add('Frameworks/App.framework/App', utf8.encode(dartSnapshot));
  extra.forEach(add);
  return Uint8List.fromList(ZipEncoder().encode(a));
}

class _LinuxIos extends IosTarget {
  @override
  bool get hostIsMacOS => false;
}

/// Answers fastlane lookup lanes with [latest] (or fails with [exit]).
class _StoreRunner implements ProcessRunner {
  _StoreRunner({this.latest = 41, this.exit = 0});
  final int latest;
  final int exit;
  final commands = <List<String>>[];
  final environments = <Map<String, String>?>[];

  @override
  Future<int> run(
    String exe,
    List<String> args, {
    String? workingDirectory,
    Map<String, String>? environment,
  }) async {
    commands.add([exe, ...args]);
    environments.add(environment);
    final out = args.where((a) => a.startsWith('output:')).firstOrNull;
    if (out != null && exit == 0) {
      File(out.substring(7)).writeAsStringSync('{"buildNumber": "$latest"}');
    }
    return exit;
  }

  @override
  Future<ProcessResult> capture(
    String exe,
    List<String> args, {
    String? workingDirectory,
  }) async => ProcessResult(0, 0, '', '');
}

const secret = 'S3CRET-VALUE-never-print';
Map<String, String> iosEnv(String id) => {
  'IOS_SIGNING_TENANT': id,
  'MATCH_GIT_URL': 'git@example.org:signing-$id.git',
  'MATCH_PASSWORD': secret,
  'ASC_KEY_ID': 'KEY123',
  'ASC_ISSUER_ID': 'issuer-uuid',
  'ASC_PRIVATE_KEY_P8_BASE64': base64.encode(utf8.encode(secret)),
  'PLATFORM_API_BASE': 'https://api.acme.com',
};

Map<String, dynamic> withAsc(Map<String, dynamic> m) {
  (m['ios'] as Map)['appStoreConnectAppId'] = '1234567890';
  (m['ios'] as Map)['sku'] = '${m['id']}-ios';
  return m;
}

ToolContext ctx(
  Fixture f, {
  Map<String, String> env = const {},
  ProcessRunner? runner,
  bool dryRun = false,
}) => ToolContext(
  ws: f.ws,
  log: Log(out: TestSink(), err: TestSink()),
  runner: runner ?? DryRunProcessRunner((_) {}),
  env: env,
  dryRun: dryRun,
);

void main() {
  late Fixture f;
  setUp(() {
    f = Fixture.create();
    // Clearly different icons per tenant (the fixture's defaults are near-identical).
    f.png('tenants/alpha/assets/app_icon.png', 1024, color: [30, 140, 230]);
    f.png('tenants/beta/assets/app_icon.png', 1024, color: [220, 40, 40]);
  });
  tearDown(() => f.dispose());

  Registry reg() => loadRegistry(f.ws).registry!;

  group('plist', () {
    test('binary plist decoder matches Python plistlib output', () {
      const ref =
          'YnBsaXN0MDDZAQIDBAUGBwgJCgsPEBESExQVXxASQ0ZCdW5kbGVJZGVudGlmaWVyUWFRYlNiaWdRZFFmUW5TbmVnVm5lc3RlZFdjb20ueC55owwNDmEA6RABI0AEAAAAAAAACRMAAAABKgXyAEIAAQgRASwT//////////nRFhdRa1F2CBswMjQ4Ojw+QklRVVhaY2RtcHF0fYCCAAAAAAAAAQEAAAAAAAAAGAAAAAAAAAAAAAAAAAAAAIQ=';
      final d = decodePlistDict(base64.decode(ref));
      expect(d['CFBundleIdentifier'], 'com.x.y');
      expect(d['n'], 300);
      expect(d['big'], 5000000000);
      expect(d['neg'], -7);
      expect(d['b'], isTrue);
      expect(d['f'], isFalse);
      expect(d['a'], ['é', 1, 2.5]);
      expect(d['d'], [0, 1]);
      expect(d['nested'], {'k': 'v'});
    });

    test('XML round trip and the test-only binary writer agree', () {
      final v = {
        'a': 'x & <y>',
        'n': 7,
        't': true,
        'l': ['p', 'q'],
        'e': <String, Object?>{},
        'u': 'Ünïcode',
      };
      expect(decodePlist(utf8.encode(encodeXmlPlist(v))), v);
      expect(decodePlist(encodeBinaryPlist(v)), v);
      expect(
        () => decodePlist(utf8.encode('<plist><foo/></plist>')),
        throwsFormatException,
      );
    });
  });

  group('ios validator', () {
    test('release needs App Store Connect ids, credentials and API base', () {
      final r = f.validate(tenants: {'alpha'}, release: true, platform: 'ios');
      expect(r, hasError('ios.appStoreConnectAppId: required'));
      expect(r, hasError('ios.sku: required'));
      expect(r, hasError('no Apple credentials'));
      expect(r, hasError('PLATFORM_API_BASE'));

      f.writeManifest('alpha', withAsc(f.store('alpha')));
      final ok = f.validate(
        tenants: {'alpha'},
        release: true,
        platform: 'ios',
        env: iosEnv('alpha'),
      );
      expect(ok.errors, isEmpty, reason: ok.errors.join('\n'));
      // Secrets are never echoed in issues.
      expect(
        r.issues.join('\n') + ok.issues.join('\n'),
        isNot(contains(secret)),
      );
    });

    test('team id required; another tenant\'s credentials are never used', () {
      final m = withAsc(f.store('alpha'));
      (m['ios'] as Map).remove('teamId');
      f.writeManifest('alpha', m);
      final r = f.validate(
        tenants: {'alpha'},
        release: true,
        platform: 'ios',
        env: iosEnv('beta'),
      );
      expect(r, hasError('ios.teamId: required'));
      expect(r, hasError('no Apple credentials'));
    });

    test('per-tenant ios/signing/<id>.env works and reports missing keys', () {
      f.writeManifest('alpha', withAsc(f.store('alpha')));
      f.file(
        'ios/signing/alpha.env',
        'MATCH_GIT_URL=git@x:y.git\nMATCH_PASSWORD="$secret"\n',
      );
      final r = f.validate(
        tenants: {'alpha'},
        release: true,
        platform: 'ios',
        env: {'PLATFORM_API_BASE': 'https://api.acme.com'},
      );
      expect(
        r,
        hasError('ios/signing/alpha.env is missing ASC_KEY_ID, ASC_ISSUER_ID'),
      );
      f.file(
        'ios/signing/alpha.env',
        'MATCH_GIT_URL=git@x:y.git\nMATCH_PASSWORD=$secret\nASC_KEY_ID=K\nASC_ISSUER_ID=I\nASC_KEY_PATH=AuthKey.p8\n',
      );
      expect(
        f.validate(
          tenants: {'alpha'},
          release: true,
          platform: 'ios',
          env: {'PLATFORM_API_BASE': 'https://api.acme.com'},
        ),
        hasError('ASC_KEY_PATH from ios/signing/alpha.env does not exist'),
      );
      f.file('ios/signing/AuthKey.p8', 'key');
      final ok = f.validate(
        tenants: {'alpha'},
        release: true,
        platform: 'ios',
        env: {'PLATFORM_API_BASE': 'https://api.acme.com'},
      );
      expect(ok.errors, isEmpty, reason: ok.errors.join('\n'));
      final c = resolveIosCredentials(f.ws, 'alpha', const {})!;
      expect(c.values['ASC_KEY_PATH'], f.path('ios/signing/AuthKey.p8'));
      expect(resolveIosCredentials(f.ws, 'beta', const {}), isNull);
    });

    test('display names that break xcconfig / Info.plist are rejected', () {
      f.writeManifest('beta', {
        ...f.store('beta'),
        'displayName': r'Beta // $(X)',
      });
      expect(f.validate(platform: 'ios'), hasError('must not contain "//"'));
    });

    test('Firebase plist must belong to the tenant bundle id', () {
      f.writeManifest('beta', {
        ...f.store('beta'),
        'push': {'enabled': true},
      });
      f.file(
        'tenants/beta/ios/GoogleService-Info.plist',
        encodeXmlPlist({'BUNDLE_ID': 'com.alpha.shop'}),
      );
      expect(
        f.validate(tenants: {'beta'}, platform: 'ios'),
        hasError('is for bundle id com.alpha.shop, expected com.beta.shop'),
      );
    });
  });

  group('ios generator', () {
    Map<String, String> overlay(String id) {
      final dir = Directory(f.path('build/workspaces/$id/overlay'));
      return {
        for (final e in dir.listSync(recursive: true).whereType<File>())
          p.relative(e.path, from: dir.path): latin1.decode(
            e.readAsBytesSync(),
          ),
      };
    }

    test('store overlay: identity, signing, entitlements, icon set, export options', () async {
      final b = f.store('beta');
      (b['store'] as Map)['extraLinkHosts'] = ['shop.beta.com'];
      f.writeManifest('beta', b);
      final r = await IosTarget().generate(ctx(f), reg().manifests['beta']!);
      expect(
        r.written,
        contains(
          'build/workspaces/beta/overlay/ios/Flutter/AppIdentity.xcconfig',
        ),
      );
      final o = overlay('beta');
      final id = parseXcconfig(o['ios/Flutter/AppIdentity.xcconfig']!);
      expect(id, {
        'APP_BUNDLE_ID': 'com.beta.shop',
        'APP_DISPLAY_NAME': 'Beta Store',
        'APP_ASSOCIATED_DOMAIN': 'beta.acme.com',
        'APP_URL_SCHEME': 'betashop',
        'DEVELOPMENT_TEAM': team,
      });
      final signing = parseXcconfig(o['ios/Flutter/TenantSigning.xcconfig']!);
      expect(signing['CODE_SIGN_STYLE'], 'Manual');
      expect(
        signing['PROVISIONING_PROFILE_SPECIFIER'],
        'match AppStore com.beta.shop',
      );
      expect(
        o['ios/Flutter/Release.xcconfig'],
        contains('#include "TenantSigning.xcconfig"'),
      );
      final ent = decodePlistDict(
        utf8.encode(o['ios/Runner/Runner.entitlements']!),
      );
      expect(ent, {
        'com.apple.developer.associated-domains': [
          'applinks:beta.acme.com',
          'applinks:shop.beta.com',
          'webcredentials:beta.acme.com',
          'webcredentials:shop.beta.com',
        ],
      });
      final export = decodePlistDict(utf8.encode(o['ExportOptions.plist']!));
      expect(export['method'], 'app-store-connect');
      expect(export['teamID'], team);
      expect(export['signingStyle'], 'manual');
      expect(export['provisioningProfiles'], {
        'com.beta.shop': 'match AppStore com.beta.shop',
      });
      expect(export['manageAppVersionAndBuildNumber'], isFalse);

      final contents = jsonDecode(o['$iosAppIconSetDir/Contents.json']!) as Map;
      final images = contents['images'] as List;
      expect(images, hasLength(19));
      for (final i in images) {
        final png = File(
          f.path(
            'build/workspaces/beta/overlay/$iosAppIconSetDir/${i['filename']}',
          ),
        );
        final decoded = img.decodePng(png.readAsBytesSync())!;
        final want =
            (double.parse((i['size'] as String).split('x').first) *
                    int.parse((i['scale'] as String).replaceAll('x', '')))
                .round();
        expect(decoded.width, want, reason: i['filename'] as String);
        expect(
          decoded.hasAlpha,
          isFalse,
          reason: 'App Store icons must be opaque',
        );
      }
      expect(o.keys.where((k) => k.contains('GoogleService')), isEmpty);
    });

    test(
      'idempotent, --check clean, and disabled iOS removes the overlay',
      () async {
        final t = IosTarget();
        await t.generate(ctx(f), reg().manifests['alpha']!);
        final again = await t.generate(ctx(f), reg().manifests['alpha']!);
        expect(again.changed, isFalse);
        expect(
          (await t.generate(
            ctx(f),
            reg().manifests['alpha']!,
            check: true,
          )).changed,
          isFalse,
        );
        f.png('tenants/alpha/assets/app_icon.png', 1024, color: [1, 200, 3]);
        expect(
          (await t.generate(
            ctx(f),
            reg().manifests['alpha']!,
            check: true,
          )).changed,
          isTrue,
        );

        final m = f.store('alpha');
        (m['ios'] as Map)['enabled'] = false;
        f.writeManifest('alpha', m);
        final r = await t.generate(ctx(f), reg().manifests['alpha']!);
        expect(
          r.deleted,
          contains(
            'build/workspaces/alpha/overlay/ios/Flutter/AppIdentity.xcconfig',
          ),
        );
      },
    );

    test('no cross-tenant leakage between platform and store overlays', () async {
      final t = IosTarget();
      for (final id in ['platform', 'alpha', 'beta']) {
        await t.generate(ctx(f), reg().manifests[id]!);
      }
      final ids = {
        'platform': ['com.acme.storeapp', 'storeapp', 'Store App'],
        'alpha': [
          'com.alpha.shop',
          'alpha.acme.com',
          'alphashop',
          'Alpha Store',
        ],
        'beta': ['com.beta.shop', 'beta.acme.com', 'betashop', 'Beta Store'],
      };
      for (final id in ids.keys) {
        final all = overlay(id).values.join('\n');
        for (final other in ids.keys.where((o) => o != id)) {
          for (final needle in ids[other]!) {
            if (ids[id]!.any((own) => own.contains(needle))) continue;
            expect(
              all,
              isNot(contains(needle)),
              reason: '$id overlay contains $other value $needle',
            );
          }
        }
      }
      // platform keeps the committed icon set and the wildcard link domain, has no signing (no team id).
      final pl = overlay('platform');
      expect(pl.keys.any((k) => k.contains('AppIcon')), isFalse);
      expect(
        pl['ios/Runner/Runner.entitlements'],
        contains('applinks:*.acme.com'),
      );
      expect(pl.containsKey('ExportOptions.plist'), isFalse);
      // Different icons per tenant.
      final a = File(
        f.path(
          'build/workspaces/alpha/overlay/$iosAppIconSetDir/Icon-App-60x60@2x.png',
        ),
      ).readAsBytesSync();
      final b = File(
        f.path(
          'build/workspaces/beta/overlay/$iosAppIconSetDir/Icon-App-60x60@2x.png',
        ),
      ).readAsBytesSync();
      expect(a, isNot(b));
    });

    test('shared tree stays the platform app; stale tenant values are refused', () async {
      const shared =
          'APP_BUNDLE_ID = com.acme.storeapp\nAPP_DISPLAY_NAME = Store App\nAPP_URL_SCHEME = storeapp\n';
      f.file('ios/Flutter/AppIdentity.xcconfig', shared);
      await IosTarget().generate(ctx(f), reg().manifests['alpha']!);
      expect(
        File(f.path('ios/Flutter/AppIdentity.xcconfig')).readAsStringSync(),
        shared,
      );

      f.file(
        'ios/Flutter/AppIdentity.xcconfig',
        'APP_BUNDLE_ID = com.alpha.shop // left over\n',
      );
      await expectLater(
        IosTarget().generate(ctx(f), reg().manifests['beta']!),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'message',
            contains('tenant "alpha"'),
          ),
        ),
      );
    });

    test(
      'prune removes only generated workspaces of disabled tenants',
      () async {
        for (final id in ['alpha', 'beta']) {
          await IosTarget().generate(ctx(f), reg().manifests[id]!);
        }
        f.file('build/workspaces/handmade/keep.txt', 'x');
        final removed = IosTarget().prune(ctx(f), {'alpha'});
        expect(removed, ['build/workspaces/beta']);
        expect(
          Directory(f.path('build/workspaces/alpha')).existsSync(),
          isTrue,
        );
        expect(
          File(f.path('build/workspaces/handmade/keep.txt')).existsSync(),
          isTrue,
        );
      },
    );
  });

  group('ios workspace', () {
    bool hasGit() {
      try {
        return Process.runSync('git', ['--version']).exitCode == 0;
      } catch (_) {
        return false;
      }
    }

    test('snapshot = sources + overlay, isolated from tenants/ and other tenants', () async {
      if (!hasGit()) return markTestSkipped('git not installed');
      expect(
        Process.runSync('git', [
          'init',
          '-q',
        ], workingDirectory: f.dir.path).exitCode,
        0,
      );
      f.file('.gitignore', '/build/\n/dist/\n/ios/signing/\n');
      f.file('lib/main.dart', 'void main() {}\n');
      f.file(
        'ios/Flutter/AppIdentity.xcconfig',
        'APP_BUNDLE_ID = com.acme.storeapp\n',
      );
      f.file(
        'ios/Flutter/Release.xcconfig',
        '#include "Generated.xcconfig"\n#include "AppIdentity.xcconfig"\n',
      );
      f.png('$iosAppIconSetDir/Icon-App-1024x1024@1x.png', 64);
      f.file('$iosAppIconSetDir/Contents.json', '{}');
      f.file('ios/signing/alpha.env', 'MATCH_PASSWORD=$secret\n');
      f.file('dist/beta/ios/beta.ipa', 'x');

      generateDartDefines(
        f.ws,
        FileSync(f.ws),
        reg().manifests['alpha']!,
        GenerateResult(),
        appVersion: '2.3.4',
      );
      await IosTarget().generate(ctx(f), reg().manifests['alpha']!);
      final w = IosWorkspace(f.ws, 'alpha');
      final first = w.sync();
      expect(first.written, isNotEmpty);
      String app(String rel) => p.join(w.appDir, rel);
      expect(File(app('lib/main.dart')).existsSync(), isTrue);
      expect(
        File(app('build/tenant_config/alpha.json')).readAsStringSync(),
        contains('"TENANT_ID": "alpha"'),
      );
      expect(
        File(app('ios/Flutter/AppIdentity.xcconfig')).readAsStringSync(),
        contains('com.alpha.shop'),
      );
      expect(
        File(app('ios/Flutter/Release.xcconfig')).readAsStringSync(),
        contains('TenantSigning'),
      );
      expect(File(app('ExportOptions.plist')).existsSync(), isTrue);
      expect(Directory(app('tenants')).existsSync(), isFalse);
      expect(Directory(app('dist')).existsSync(), isFalse);
      expect(Directory(app('ios/signing')).existsSync(), isFalse);
      // The committed platform icon set is replaced, not merged.
      expect(
        File(app('$iosAppIconSetDir/Icon-App-1024x1024@1x.png'))
            .readAsBytesSync(),
        File(
          f.path(
            'build/workspaces/alpha/overlay/$iosAppIconSetDir/Icon-App-1024x1024@1x.png',
          ),
        ).readAsBytesSync(),
      );
      expect(
        File(app('$iosAppIconSetDir/Contents.json')).readAsStringSync(),
        contains('tenant_builder'),
      );
      // Shared tree untouched.
      expect(
        File(f.path('ios/Flutter/AppIdentity.xcconfig')).readAsStringSync(),
        contains('com.acme.storeapp'),
      );

      // Second sync: nothing rewritten; build outputs in app/ survive; removed sources go away.
      File(app('build/ios/cache.bin'))
        ..createSync(recursive: true)
        ..writeAsStringSync('cache');
      expect(w.sync().changed, isFalse);
      File(f.path('lib/main.dart')).deleteSync();
      final third = w.sync();
      expect(
        third.deleted,
        contains(p.relative(app('lib/main.dart'), from: f.dir.path)),
      );
      expect(File(app('build/ios/cache.bin')).existsSync(), isTrue);
    });

    test('sync without generate fails clearly', () {
      expect(
        () => IosWorkspace(f.ws, 'alpha').sync(sources: const []),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'm',
            contains('run generate'),
          ),
        ),
      );
    });
  });

  group('ipa verifier', () {
    late img.Image alphaIcon;
    late img.Image betaIcon;
    setUp(() {
      alphaIcon = decodePngFile(
        File(f.path('tenants/alpha/assets/app_icon.png')),
      );
      betaIcon = decodePngFile(
        File(f.path('tenants/beta/assets/app_icon.png')),
      );
    });

    IpaExpectations expect0([Map<String, dynamic>? info]) =>
        IosTarget().expectations(
          f.ws,
          reg(),
          reg().manifests['alpha']!,
          info ??
              {'versionName': '2.3.4', 'buildNumber': 57, 'mode': 'release'},
        );

    List<int> icon(img.Image i) => resizePng(i, 120);

    test('a correct IPA passes (binary Info.plist, CgBI icon)', () {
      final r = verifyIpa(
        syntheticIpa(
          iconPng: cgbiPng(img.copyResize(alphaIcon, width: 120, height: 120)),
        ),
        expect0(),
      );
      expect(r.problems, isEmpty, reason: r.problems.join('\n'));
      expect(r.notes.join('\n'), contains('app icon matches tenants/alpha'));
      expect(
        r.notes.join('\n'),
        contains('Dart code is bound to alpha.acme.com'),
      );
      expect(r.facts['bundleId'], 'com.alpha.shop');
      expect(r.facts['profile'], 'match AppStore com.alpha.shop');
    });

    test('wrong identity, version, schemes and icon are reported', () {
      final r = verifyIpa(
        syntheticIpa(
          info: infoPlist(
            bundleId: 'com.beta.shop',
            name: 'Beta Store',
            version: '2.3.3',
            build: '56',
            schemes: ['alphashop', 'betashop'],
          ),
          iconPng: icon(betaIcon),
        ),
        expect0(),
      );
      final all = r.problems.join('\n');
      expect(all, contains('CFBundleIdentifier is com.beta.shop'));
      expect(all, contains('CFBundleDisplayName is "Beta Store"'));
      expect(all, contains('CFBundleShortVersionString is 2.3.3'));
      expect(all, contains('CFBundleVersion is 56, expected 57'));
      expect(all, contains('URL schemes are alphashop, betashop'));
      expect(all, contains('does not match tenants/alpha icon'));
      expect(all, contains('looks like the icon of tenant beta'));
      expect(all, contains('contains "com.beta.shop" of tenant beta'));
    });

    test('signing: team, development entitlements, domains, extra capabilities, profile', () {
      final r = verifyIpa(
        syntheticIpa(
          iconPng: icon(alphaIcon),
          entitlements: signedEntitlements(
            appId: 'ZZZZZ99999.com.alpha.shop',
            getTaskAllow: true,
            domains: ['applinks:beta.acme.com'],
            extra: {
              'com.apple.developer.icloud-services': ['CloudKit'],
              'aps-environment': 'production',
            },
          ),
          embeddedProfile: profile(
            appId: 'ZZZZZ99999.com.alpha.shop',
            devices: true,
            expires: DateTime.utc(2020),
          ),
        ),
        expect0(),
      );
      final all = r.problems.join('\n');
      expect(
        all,
        contains('application-identifier is ZZZZZ99999.com.alpha.shop'),
      );
      expect(all, contains('signed for team ZZZZZ99999, expected $team'));
      expect(all, contains('get-task-allow is true'));
      expect(all, contains('associated domains are applinks:beta.acme.com'));
      expect(all, contains('aps-environment is set but push is disabled'));
      expect(
        all,
        contains(
          'unexpected entitlements (capabilities not used): com.apple.developer.icloud-services',
        ),
      );
      expect(
        all,
        contains('provisioning profile is for ZZZZZ99999.com.alpha.shop'),
      );
      expect(all, contains('lists devices'));
      expect(all, contains('expired'));
      expect(all, contains('not allowed by the provisioning profile'));
      expect(all, contains('contains "beta.acme.com" of tenant beta'));
    });

    test('unsigned IPA, missing purpose strings, stray Firebase plist', () {
      final info = infoPlist()..remove('NSCameraUsageDescription');
      final a = Archive()
        ..addFile(
          ArchiveFile.bytes(
            'Payload/Runner.app/Info.plist',
            encodeBinaryPlist(info),
          ),
        )
        ..addFile(ArchiveFile.bytes('Payload/Runner.app/Runner', [1, 2, 3]))
        ..addFile(
          ArchiveFile.bytes(
            'Payload/Runner.app/GoogleService-Info.plist',
            utf8.encode(encodeXmlPlist({'BUNDLE_ID': 'com.alpha.shop'})),
          ),
        );
      final r = verifyIpa(ZipEncoder().encode(a), expect0());
      final all = r.problems.join('\n');
      expect(all, contains('not signed'));
      expect(all, contains('NSCameraUsageDescription is missing'));
      expect(
        all,
        contains('GoogleService-Info.plist is bundled but push is disabled'),
      );
      expect(all, contains('no compiled app icon'));
      expect(
        verifyIpa([1, 2, 3], expect0()).problems.single,
        contains('not a valid IPA'),
      );
    });

    test(
      'verify command: platform IPA must not contain store tenants',
      () async {
        final saved = targets['ios']!;
        targets['ios'] = _LinuxIos();
        addTearDown(() => targets['ios'] = saved);
        f.png(
          '$iosAppIconSetDir/Icon-App-1024x1024@1x.png',
          1024,
          color: [7, 7, 7],
        );
        final platformIcon = decodePngFile(
          File(f.path('$iosAppIconSetDir/Icon-App-1024x1024@1x.png')),
        );
        final ipa = f.path('platform.ipa');
        File(ipa).writeAsBytesSync(
          syntheticIpa(
            info: infoPlist(
              bundleId: 'com.acme.storeapp',
              name: 'Store App',
              schemes: ['storeapp'],
            ),
            entitlements: signedEntitlements(
              appId: '$team.com.acme.storeapp',
              domains: ['applinks:*.acme.com', 'webcredentials:*.acme.com'],
            ),
            embeddedProfile: profile(appId: '$team.com.acme.storeapp'),
            iconPng: resizePng(platformIcon, 120),
            dartSnapshot: 'generic store browser',
          ),
        );
        // platform has no team id in the fixture: identity checks still run, team checks are skipped.
        var (code, out) = await cli(f, [
          'verify',
          '--tenant',
          'platform',
          '--platform',
          'ios',
          '--artifact-path',
          ipa,
        ]);
        expect(code, 0, reason: out);
        expect(out, contains('codesign --verify skipped'));

        File(ipa).writeAsBytesSync(
          syntheticIpa(
            info: infoPlist(
              bundleId: 'com.acme.storeapp',
              name: 'Store App',
              schemes: ['storeapp'],
            ),
            entitlements: signedEntitlements(
              appId: '$team.com.acme.storeapp',
              domains: ['applinks:*.acme.com', 'webcredentials:*.acme.com'],
            ),
            embeddedProfile: profile(appId: '$team.com.acme.storeapp'),
            iconPng: resizePng(platformIcon, 120),
            dartSnapshot: 'generic',
            extra: {'flutter_assets/config.txt': utf8.encode('alpha.acme.com')},
          ),
        );
        (code, out) = await cli(f, [
          'verify',
          '--tenant',
          'platform',
          '--platform',
          'ios',
          '--artifact-path',
          ipa,
        ]);
        expect(code, 1);
        expect(out, contains('contains "alpha.acme.com" of tenant alpha'));
      },
    );
  });

  group('ios build / upload', () {
    test('build --dry-run: no --flavor, define file, export options, match + lookup lanes, no secrets', () async {
      final lines = <String>[];
      final out = TestSink();
      final code = await runCli(
        [
          '--root',
          f.dir.path,
          'build',
          '--tenant',
          'alpha',
          '--platform',
          'ios',
          '--dry-run',
        ],
        log: Log(out: out, err: out),
        env: iosEnv('alpha'),
        runner: DryRunProcessRunner(lines.add),
      );
      final text = out.buf.toString();
      expect(code, 0, reason: text);
      expect(
        text,
        contains(
          'bundle exec fastlane ios latest_build_number bundle_id:com.alpha.shop output:',
        ),
      );
      expect(
        text,
        contains(
          'bundle exec fastlane ios match_readonly bundle_id:com.alpha.shop team_id:$team',
        ),
      );
      expect(
        text,
        contains(
          'flutter build ipa --release -t lib/main.dart --dart-define-from-file=build/tenant_config/alpha.json '
          '--build-name 2.3.4 --build-number 56 --export-options-plist=ExportOptions.plist',
        ),
      );
      expect(text, isNot(contains('--flavor')));
      expect(text, contains('dist/alpha/ios/alpha-2.3.4+56.ipa'));
      expect(text, contains('MATCH_PASSWORD=…'));
      expect(text, isNot(contains(secret)));
      expect(text, isNot(contains(base64.encode(utf8.encode(secret)))));
      expect(
        File(f.path('build/workspaces/alpha/overlay/ExportOptions.plist'))
            .existsSync(),
        isTrue,
      );
    });

    test(
      'real build fails closed on Linux, and before that on missing lookup',
      () async {
        final saved = targets['ios']!;
        targets['ios'] = _LinuxIos();
        addTearDown(() => targets['ios'] = saved);
        f.writeManifest('alpha', withAsc(f.store('alpha')));
        // Lookup cannot produce a number (dry-run runner writes nothing) and no override -> stop.
        var (code, out) = await cli(f, [
          'build',
          '--tenant',
          'alpha',
          '--platform',
          'ios',
        ], env: iosEnv('alpha'));
        expect(code, 1);
        expect(out, contains('store build-number lookup failed'));
        expect(out, contains('Pass --build-number N'));
        // With a working lookup the build reaches the platform gate.
        (code, out) = await cli(
          f,
          ['build', '--tenant', 'alpha', '--platform', 'ios'],
          env: iosEnv('alpha'),
          runner: _StoreRunner(latest: 9),
        );
        expect(code, 1);
        expect(out, contains('latest build number in the store: 9'));
        expect(out, contains('version 2.3.4+10'));
        expect(out, contains('need macOS with Xcode'));
      },
    );

    test('build number policy: store +1, override must be higher, lookup failure needs override', () async {
      final m = loadRegistry(f.ws).registry!.manifests['alpha']!;
      final env = iosEnv('alpha');
      final t = IosTarget();
      expect(
        await buildNumberFor(
          ctx(f, env: env, runner: _StoreRunner(latest: 41)),
          t,
          m,
          'release',
          null,
          1,
        ),
        42,
      );
      expect(
        await buildNumberFor(
          ctx(f, env: env, runner: _StoreRunner(latest: 41)),
          t,
          m,
          'release',
          50,
          1,
        ),
        50,
      );
      await expectLater(
        buildNumberFor(
          ctx(f, env: env, runner: _StoreRunner(latest: 41)),
          t,
          m,
          'release',
          41,
          1,
        ),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'm',
            contains('not higher than the store\'s latest (41)'),
          ),
        ),
      );
      await expectLater(
        buildNumberFor(
          ctx(f, env: env, runner: _StoreRunner(exit: 1)),
          t,
          m,
          'release',
          null,
          1,
        ),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'm',
            contains('--build-number'),
          ),
        ),
      );
      expect(
        await buildNumberFor(
          ctx(f, env: env, runner: _StoreRunner(exit: 1)),
          t,
          m,
          'release',
          7,
          1,
        ),
        7,
      );
      // No credentials at all: cannot look up -> needs an override.
      await expectLater(
        buildNumberFor(
          ctx(f, runner: _StoreRunner()),
          t,
          m,
          'release',
          null,
          1,
        ),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'm',
            contains('no App Store Connect API key'),
          ),
        ),
      );
      expect(
        await buildNumberFor(
          ctx(f, runner: _StoreRunner()),
          t,
          m,
          'debug',
          null,
          3,
        ),
        3,
      );
      // Credentials go to fastlane through the environment, not the command line.
      final runner = _StoreRunner();
      await buildNumberFor(
        ctx(f, env: env, runner: runner),
        t,
        m,
        'release',
        null,
        1,
      );
      expect(runner.commands.single.join(' '), isNot(contains(secret)));
      expect(runner.environments.single!['MATCH_PASSWORD'], secret);
    });

    test('android lookup uses google_play_track_version_codes lane', () async {
      final m = loadRegistry(f.ws).registry!.manifests['alpha']!;
      final runner = _StoreRunner(latest: 120);
      final key = f.path('play.json');
      File(key).writeAsStringSync('{}');
      final n = await buildNumberFor(
        ctx(f, env: {'PLAY_SERVICE_ACCOUNT_JSON_PATH': key}, runner: runner),
        AndroidTarget(),
        m,
        'release',
        null,
        1,
      );
      expect(n, 121);
      expect(runner.commands.single.sublist(0, 5), [
        'bundle',
        'exec',
        'fastlane',
        'android',
        'latest_version_code',
      ]);
      await expectLater(
        buildNumberFor(
          ctx(f, runner: runner),
          AndroidTarget(),
          m,
          'release',
          null,
          1,
        ),
        throwsA(
          isA<ToolException>().having(
            (e) => e.message,
            'm',
            contains('PLAY_SERVICE_ACCOUNT_JSON_PATH'),
          ),
        ),
      );
    });

    test('upload: TestFlight only, needs the App Store Connect app id, pilot via lane', () async {
      var (code, out) = await cli(f, [
        'upload',
        '--tenant',
        'alpha',
        '--platform',
        'ios',
        '--artifact-path',
        'x.ipa',
        '--dry-run',
      ], env: iosEnv('alpha'));
      expect(code, 1);
      expect(out, contains('ios.appStoreConnectAppId is required for upload'));

      f.writeManifest('alpha', withAsc(f.store('alpha')));
      (code, out) = await cli(f, [
        'upload',
        '--tenant',
        'alpha',
        '--platform',
        'ios',
        '--artifact-path',
        'x.ipa',
        '--dry-run',
      ], env: iosEnv('alpha'));
      expect(code, 0, reason: out);
      expect(out, contains('fastlane ios upload_testflight ipa:'));
      expect(out, contains('bundle_id:com.alpha.shop app_id:1234567890'));
      expect(out, isNot(contains(secret)));

      (code, out) = await cli(f, [
        'upload',
        '--tenant',
        'alpha',
        '--platform',
        'ios',
        '--destination',
        'appstore',
        '--dry-run',
      ], env: iosEnv('alpha'));
      expect(code, usageExit);

      // A real upload re-verifies the IPA and refuses a bad one.
      final saved = targets['ios']!;
      targets['ios'] = _LinuxIos();
      addTearDown(() => targets['ios'] = saved);
      final bad = f.path('bad.ipa');
      File(bad).writeAsBytesSync(
        syntheticIpa(info: infoPlist(bundleId: 'com.beta.shop')),
      );
      final runner = _StoreRunner();
      (code, out) = await cli(
        f,
        [
          'upload',
          '--tenant',
          'alpha',
          '--platform',
          'ios',
          '--artifact-path',
          bad,
        ],
        env: iosEnv('alpha'),
        runner: runner,
      );
      expect(code, 1);
      expect(out, contains('refusing to upload alpha'));
      expect(runner.commands, isEmpty);
    });
  });

  group('release safety', () {
    test(
      'Fastfile has the expected lanes and nothing that submits or releases',
      () {
        final src = File('fastlane/Fastfile').readAsStringSync();
        final code = src
            .split('\n')
            .where((l) => !l.trimLeft().startsWith('#'))
            .join('\n');
        for (final lane in [
          'latest_build_number',
          'match_readonly',
          'upload_testflight',
          'upload_testing',
          'latest_version_code',
        ]) {
          expect(code, contains('lane :$lane do'));
        }
        expect(code, contains('readonly: true'));
        expect(code, contains('skip_submission: true'));
        for (final forbidden in [
          RegExp(r'\bdeliver\b'),
          RegExp(r'\bupload_to_app_store\b'),
          RegExp(r'\bappstore\s*\('),
          RegExp(r'submit_for_review'),
          RegExp(r'automatic_release'),
          RegExp(r'track_promote_to'),
          RegExp(r'distribute_external:\s*true'),
          RegExp(r'readonly:\s*false'),
        ]) {
          expect(
            forbidden.hasMatch(code),
            isFalse,
            reason: 'Fastfile must not use ${forbidden.pattern}',
          );
        }
      },
    );

    test('ios workflow: macOS, per-tenant environment + concurrency, cleanup always', () {
      final file = File('../.github/workflows/ios-tenant-release.yml');
      final y = loadYaml(file.readAsStringSync()) as YamlMap;
      final jobs = y['jobs'] as YamlMap;
      final release = jobs['release'] as YamlMap;
      expect('${release['runs-on']}', contains('macos-'));
      expect('${release['environment']}', contains('matrix.environment'));
      expect(
        '${(release['concurrency'] as YamlMap)['group']}',
        'release-ios-\${{ matrix.id }}',
      );
      final steps = (release['steps'] as YamlList).cast<YamlMap>();
      final cleanup = steps.where(
        (s) => '${s['name']}'.toLowerCase().contains('clean up'),
      );
      expect(cleanup, isNotEmpty);
      expect(cleanup.first['if'], 'always()');
      expect(file.readAsStringSync(), isNot(contains('deliver')));
      final inputs =
          ((y['on'] as YamlMap)['workflow_dispatch'] as YamlMap)['inputs']
              as YamlMap;
      expect((inputs['action'] as YamlMap)['options'], [
        'build',
        'upload_testflight',
      ]);
    });
  });
}
