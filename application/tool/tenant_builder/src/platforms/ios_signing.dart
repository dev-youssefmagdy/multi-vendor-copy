import 'dart:io';

import 'package:path/path.dart' as p;

import '../workspace.dart';

/// Per-tenant Apple credentials (PUBLISH_TO_STORES.md §6, §11). Resolved only
/// after the tenant is selected; values go to fastlane through the child
/// process environment and are never logged or written to artifacts.
///
/// Sources, in order (never another tenant's):
///  1. Environment, when `IOS_SIGNING_TENANT=<id>` (CI: the job's GitHub
///     Environment `tenant-<id>-ios`).
///  2. `ios/signing/<id>.env` (gitignored, local machines): `KEY=VALUE` lines.
class IosCredentials {
  IosCredentials(this.description, this.values);

  /// Where the values came from (safe to print).
  final String description;

  /// Variables handed to fastlane (subset of [iosCredentialKeys]).
  final Map<String, String> values;

  bool get hasMatch => matchKeys.every((k) => (values[k] ?? '').isNotEmpty);
  bool get hasAppStoreConnect =>
      ascKeys.every((k) => (values[k] ?? '').isNotEmpty) &&
      ((values['ASC_PRIVATE_KEY_P8_BASE64'] ?? '').isNotEmpty ||
          (values['ASC_KEY_PATH'] ?? '').isNotEmpty);

  List<String> get missing => [
    for (final k in [...matchKeys, ...ascKeys])
      if ((values[k] ?? '').isEmpty) k,
    if ((values['ASC_PRIVATE_KEY_P8_BASE64'] ?? '').isEmpty &&
        (values['ASC_KEY_PATH'] ?? '').isEmpty)
      'ASC_PRIVATE_KEY_P8_BASE64 (or ASC_KEY_PATH)',
  ];
}

/// fastlane match (read-only) for the tenant's own signing storage.
const matchKeys = ['MATCH_GIT_URL', 'MATCH_PASSWORD'];

/// App Store Connect API key (role App Manager) of the tenant's account.
const ascKeys = ['ASC_KEY_ID', 'ASC_ISSUER_ID'];

/// Everything that may be passed through. `MATCH_KEYCHAIN_*` is the CI job's
/// temporary keychain (not tenant specific, taken from the environment).
const iosCredentialKeys = [
  ...matchKeys,
  ...ascKeys,
  'ASC_PRIVATE_KEY_P8_BASE64',
  'ASC_KEY_PATH',
  'MATCH_GIT_BRANCH',
];
const _passThrough = ['MATCH_KEYCHAIN_NAME', 'MATCH_KEYCHAIN_PASSWORD'];

String iosSigningEnvFile(Workspace ws, String id) =>
    p.join(ws.path('ios/signing'), '$id.env');

IosCredentials? resolveIosCredentials(
  Workspace ws,
  String tenantId,
  Map<String, String> env,
) {
  Map<String, String>? values;
  String? description;
  if (env['IOS_SIGNING_TENANT'] == tenantId) {
    values = {
      for (final k in iosCredentialKeys)
        if ((env[k] ?? '').isNotEmpty) k: env[k]!,
    };
    description = 'environment (IOS_SIGNING_TENANT=$tenantId)';
  } else {
    final f = File(iosSigningEnvFile(ws, tenantId));
    if (f.existsSync()) {
      values = {};
      for (final line in f.readAsLinesSync()) {
        final t = line.trim();
        if (t.isEmpty || t.startsWith('#')) continue;
        final i = t.indexOf('=');
        if (i <= 0) continue;
        final k = t.substring(0, i).trim().replaceFirst('export ', '');
        var v = t.substring(i + 1).trim();
        if (v.length >= 2 &&
            ((v.startsWith('"') && v.endsWith('"')) ||
                (v.startsWith("'") && v.endsWith("'")))) {
          v = v.substring(1, v.length - 1);
        }
        if (iosCredentialKeys.contains(k) && v.isNotEmpty) values[k] = v;
      }
      final keyPath = values['ASC_KEY_PATH'];
      if (keyPath != null && !p.isAbsolute(keyPath)) {
        values['ASC_KEY_PATH'] = p.normalize(p.join(f.parent.path, keyPath));
      }
      description = ws.rel(f.path);
    }
  }
  if (values == null) return null;
  for (final k in _passThrough) {
    if ((env[k] ?? '').isNotEmpty) values[k] = env[k]!;
  }
  return IosCredentials(description!, values);
}
