import 'dart:io';

import 'package:path/path.dart' as p;

import '../workspace.dart';

/// Mirrors the release-signing lookup in android/app/build.gradle.kts so
/// `validate --release` can fail before a long build does. Never returns or
/// prints the passwords themselves.
class AndroidSigningSource {
  AndroidSigningSource(this.description, this.storeFile);
  final String description;
  final String storeFile;
}

const signingEnvKeys = [
  'ANDROID_KEYSTORE_PATH',
  'ANDROID_KEYSTORE_PASSWORD',
  'ANDROID_KEY_ALIAS',
  'ANDROID_KEY_PASSWORD',
];

AndroidSigningSource? resolveAndroidSigning(
  Workspace ws,
  String tenantId,
  Map<String, String> env,
) {
  if (env['ANDROID_SIGNING_TENANT'] == tenantId &&
      signingEnvKeys.every((k) => (env[k] ?? '').isNotEmpty)) {
    return AndroidSigningSource(
      'environment (ANDROID_SIGNING_TENANT=$tenantId)',
      env['ANDROID_KEYSTORE_PATH']!,
    );
  }
  final perTenant = File(
    p.join(ws.androidDir, 'keystores', '$tenantId.properties'),
  );
  final fromFile = _fromProperties(perTenant);
  if (fromFile != null) {
    return AndroidSigningSource(ws.rel(perTenant.path), fromFile);
  }
  if (tenantId == 'platform') {
    final legacy = File(p.join(ws.androidDir, 'key.properties'));
    final f = _fromProperties(legacy);
    if (f != null) return AndroidSigningSource(ws.rel(legacy.path), f);
  }
  return null;
}

String? _fromProperties(File f) {
  if (!f.existsSync()) return null;
  final props = <String, String>{};
  for (final line in f.readAsLinesSync()) {
    final t = line.trim();
    if (t.isEmpty || t.startsWith('#') || t.startsWith('!')) continue;
    final i = t.indexOf('=');
    if (i <= 0) continue;
    props[t.substring(0, i).trim()] = t.substring(i + 1).trim();
  }
  const keys = ['storeFile', 'storePassword', 'keyAlias', 'keyPassword'];
  if (!keys.every((k) => (props[k] ?? '').isNotEmpty)) return null;
  final store = props['storeFile']!;
  return p.isAbsolute(store) ? store : p.join(f.parent.path, store);
}
