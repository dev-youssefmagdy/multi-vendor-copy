/// Shared test fixture for tool/tenant_builder (tenant_builder_test.dart,
/// tenant_builder_ios_test.dart).
library;

import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:image/image.dart' as img;
import 'package:path/path.dart' as p;

import '../../tool/tenant_builder/tenant_builder.dart';

/// A throwaway Flutter-project layout with a tenant registry.
class Fixture {
  Fixture._(this.dir);
  final Directory dir;
  Workspace get ws => Workspace(dir.path);

  static Fixture create() {
    final d = Directory.systemTemp.createTempSync('tenant_builder_test');
    final f = Fixture._(d);
    f.file('pubspec.yaml', 'name: application\nversion: 2.3.4+56\n');
    f.png('tenants/_template/assets/app_icon.png', 1024, color: [9, 9, 9]);
    f.file(
      'tenants/_template/tenant.json',
      jsonEncode(f.store('__TENANT_ID__')),
    );
    f.png(
      'android/app/src/main/res/mipmap-mdpi/ic_launcher.png',
      48,
      color: [1, 2, 3],
    );
    f.addTenant(f.platform());
    f.addTenant(f.store('alpha'));
    f.addTenant(f.store('beta'));
    return f;
  }

  void dispose() => dir.deleteSync(recursive: true);

  String path(String rel) => p.join(dir.path, rel);

  void file(String rel, String content) {
    final f = File(path(rel));
    f.parent.createSync(recursive: true);
    f.writeAsStringSync(content);
  }

  void png(
    String rel,
    int size, {
    List<int> color = const [10, 120, 200],
    bool alpha = false,
  }) {
    final image = img.Image(
      width: size,
      height: size,
      numChannels: alpha ? 4 : 3,
    );
    img.fill(
      image,
      color: alpha
          ? img.ColorRgba8(color[0], color[1], color[2], 100)
          : img.ColorRgb8(color[0], color[1], color[2]),
    );
    final f = File(path(rel));
    f.parent.createSync(recursive: true);
    f.writeAsBytesSync(img.encodePng(image));
  }

  Map<String, dynamic> platform() => {
    'schemaVersion': 1,
    'id': 'platform',
    'type': 'platform',
    'displayName': 'Store App',
    'android': {
      'enabled': true,
      'applicationId': 'com.acme.storeapp',
      'urlScheme': 'storeapp',
    },
    'ios': {
      'enabled': true,
      'bundleId': 'com.acme.storeapp',
      'urlScheme': 'storeapp',
    },
    'branding': <String, dynamic>{},
    'push': {'enabled': false},
  };

  Map<String, dynamic> store(String id) => {
    'schemaVersion': 1,
    'id': id,
    'type': 'store',
    'displayName': '${id[0].toUpperCase()}${id.substring(1)} Store',
    'store': {'host': '$id.acme.com', 'extraLinkHosts': <String>[]},
    'android': {
      'enabled': true,
      'applicationId': 'com.$id.shop',
      'urlScheme': '${id}shop',
    },
    'ios': {
      'enabled': true,
      'bundleId': 'com.$id.shop',
      'urlScheme': '${id}shop',
      'teamId': 'ABCDE12345',
    },
    'branding': {
      'appIcon': 'assets/app_icon.png',
      'adaptiveIconBackground': '#FFFFFF',
    },
    'push': {'enabled': false},
  };

  final _entries = <Map<String, dynamic>>[];

  void addTenant(
    Map<String, dynamic> manifest, {
    bool enabled = true,
    bool icon = true,
  }) {
    final id = manifest['id'] as String;
    writeManifest(id, manifest);
    if (icon && manifest['type'] == 'store') {
      png(
        'tenants/$id/assets/app_icon.png',
        1024,
        color: [id.codeUnitAt(0), 80, 160],
      );
    }
    _entries.removeWhere((e) => e['id'] == id);
    _entries.add({'id': id, 'enabled': enabled});
    writeIndex();
  }

  void writeManifest(String id, Map<String, dynamic> m) => file(
    'tenants/$id/tenant.json',
    const JsonEncoder.withIndent('  ').convert(m),
  );

  void writeIndex({
    String domain = 'acme.com',
    int schema = 1,
    List<Map<String, dynamic>>? entries,
  }) => file(
    'tenants/index.json',
    jsonEncode({
      'schemaVersion': schema,
      'platformDomain': domain,
      'tenants': entries ?? _entries,
    }),
  );

  ValidationReport validate({
    Set<String>? tenants,
    bool release = false,
    String? platform,
    bool requireEnabled = false,
    Map<String, String> env = const {},
    String? apiBase,
  }) => Validator(ws).validate(
    loadRegistry(ws),
    ValidationOptions(
      tenants: tenants,
      release: release,
      platform: platform,
      requireEnabled: requireEnabled,
      env: env,
      apiBase: apiBase,
    ),
  );
}

Matcher hasError(String fragment) => predicate<ValidationReport>(
  (r) => r.errors.any((e) => '${e.where}: ${e.message}'.contains(fragment)),
  'has an error containing "$fragment"',
);

class TestSink implements IOSink {
  final buf = StringBuffer();
  @override
  void writeln([Object? o = '']) => buf.writeln(o);
  @override
  void write(Object? o) => buf.write(o);
  @override
  dynamic noSuchMethod(Invocation i) => null;
}

Future<(int, String)> cli(
  Fixture f,
  List<String> args, {
  Map<String, String> env = const {},
  ProcessRunner? runner,
}) async {
  final out = TestSink();
  final code = await runCli(
    ['--root', f.dir.path, ...args],
    log: Log(out: out, err: out),
    env: env,
    runner: runner ?? DryRunProcessRunner((_) {}),
  );
  return (code, out.buf.toString());
}
