import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:path/path.dart' as p;
import 'package:yaml/yaml.dart';

import 'model.dart';
import 'workspace.dart';

/// Version from pubspec.yaml (`1.4.0+7` -> name 1.4.0, number 7).
class AppVersion {
  const AppVersion(this.name, this.number);
  final String name;
  final int number;
  @override
  String toString() => '$name+$number';

  static AppVersion fromPubspec(Workspace ws) {
    final doc =
        loadYaml(File(ws.path('pubspec.yaml')).readAsStringSync()) as YamlMap;
    final v = '${doc['version'] ?? '1.0.0+1'}';
    final parts = v.split('+');
    return AppVersion(
      parts[0],
      parts.length > 1 ? int.tryParse(parts[1]) ?? 1 : 1,
    );
  }
}

/// Outcome of writing one generated tree.
class GenerateResult {
  final written = <String>[];
  final deleted = <String>[];
  final unchanged = <String>[];
  bool get changed => written.isNotEmpty || deleted.isNotEmpty;
}

/// Deterministic, idempotent file writer: only touches files whose bytes change.
class FileSync {
  FileSync(this.ws, {this.check = false});
  final Workspace ws;

  /// Check mode: report what would change, write nothing.
  final bool check;

  void write(String path, List<int> bytes, GenerateResult r) {
    final f = File(path);
    if (f.existsSync() && _same(f.readAsBytesSync(), bytes)) {
      r.unchanged.add(ws.rel(path));
      return;
    }
    r.written.add(ws.rel(path));
    if (check) return;
    f.parent.createSync(recursive: true);
    f.writeAsBytesSync(bytes, flush: true);
  }

  void writeString(String path, String s, GenerateResult r) =>
      write(path, utf8.encode(s), r);

  /// Makes [dir] contain exactly [files] (relative path -> bytes).
  void syncTree(String dir, Map<String, List<int>> files, GenerateResult r) {
    final keep = <String>{};
    final names = files.keys.toList()..sort();
    for (final rel in names) {
      final full = p.join(dir, rel);
      keep.add(p.normalize(full));
      write(full, files[rel]!, r);
    }
    final d = Directory(dir);
    if (!d.existsSync()) return;
    final existing =
        d
            .listSync(recursive: true)
            .whereType<File>()
            .map((f) => p.normalize(f.path))
            .toList()
          ..sort();
    for (final f in existing) {
      if (!keep.contains(f)) {
        r.deleted.add(ws.rel(f));
        if (!check) File(f).deleteSync();
      }
    }
    if (!check) _removeEmptyDirs(d);
  }

  void _removeEmptyDirs(Directory d) {
    for (final sub in d.listSync().whereType<Directory>()) {
      _removeEmptyDirs(sub);
      if (sub.listSync().isEmpty) sub.deleteSync();
    }
  }

  static bool _same(Uint8List a, List<int> b) {
    if (a.length != b.length) return false;
    for (var i = 0; i < a.length; i++) {
      if (a[i] != b[i]) return false;
    }
    return true;
  }
}

/// Dart side of the tenant binding: `build/tenant_config/<id>.json`, passed to
/// flutter with `--dart-define-from-file`. Public values only (no secrets).
///
/// Keys (read in lib/core/config/env.dart):
///  TENANT_ID, TENANT_HOST, TENANT_NAME, TENANT_LINK_HOSTS, APP_VERSION,
///  PLATFORM_API_BASE (only when provided).
Map<String, String> dartDefinesFor(
  TenantManifest m, {
  required String appVersion,
  String? apiBase,
}) {
  final defines = <String, String>{
    'APP_URL_SCHEME': appUrlSchemeFor(m),
    'APP_VERSION': appVersion,
    'TENANT_HOST': m.isPlatform ? '' : (m.storeHost ?? ''),
    'TENANT_ID': m.id,
    'TENANT_LINK_HOSTS': m.isPlatform ? '' : m.extraLinkHosts.join(','),
    'TENANT_NAME': m.displayName,
  };
  if (apiBase != null && apiBase.isNotEmpty) {
    defines['PLATFORM_API_BASE'] = apiBase.replaceAll(RegExp(r'/+$'), '');
  }
  return defines;
}

/// Custom URL scheme the binary registers (payment return / "Open in app"). The app sends it as
/// `X-App-Scheme`; the backend only accepts the platform scheme or the store's configured one.
String appUrlSchemeFor(TenantManifest m) =>
    (m.androidEnabled ? m.androidScheme : null) ??
    (m.iosEnabled ? m.iosScheme : null) ??
    '';

String encodeDefines(Map<String, String> d) {
  final keys = d.keys.toList()..sort();
  return '${const JsonEncoder.withIndent('  ').convert({for (final k in keys) k: d[k]})}\n';
}

void generateDartDefines(
  Workspace ws,
  FileSync sync,
  TenantManifest m,
  GenerateResult r, {
  required String appVersion,
  String? apiBase,
}) {
  sync.writeString(
    ws.defineFile(m.id),
    encodeDefines(dartDefinesFor(m, appVersion: appVersion, apiBase: apiBase)),
    r,
  );
}

String xmlEscape(String s) => s
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;');
