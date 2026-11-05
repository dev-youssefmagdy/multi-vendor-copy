import 'dart:convert';
import 'dart:io';

import 'package:path/path.dart' as p;

import '../generator.dart';
import '../workspace.dart';
import 'ios_native.dart';

/// Isolated per-tenant iOS build workspace:
///
/// ```text
/// build/workspaces/<id>/
///   .tenant_builder.json   marker (prune only removes folders that have it)
///   overlay/               tenant files written by `generate` (ios_native.dart)
///   app/                   project snapshot + overlay; `flutter build ipa` runs here
///   .lock                  held while a build/run uses the workspace
/// ```
///
/// The snapshot is the project's source files as `git ls-files --cached
/// --others --exclude-standard` sees them (tracked + new, not ignored), so a
/// local build includes uncommitted changes and never sees `build/`, `dist/`,
/// keys or other tenants' assets (`tenants/` and `android/` are not copied).
/// Files are only rewritten when their bytes differ, so Xcode/Flutter caches
/// in `app/` survive between builds of the same tenant.
class IosWorkspace {
  IosWorkspace(this.ws, this.id);
  final Workspace ws;
  final String id;

  String get root => ws.path('build/workspaces/$id');
  String get overlayDir => p.join(root, 'overlay');
  String get appDir => p.join(root, 'app');
  String get markerPath => p.join(root, markerName);
  String get _managedList => p.join(appDir, '.tenant_builder_files');
  static const markerName = '.tenant_builder.json';

  /// Top-level folders never copied into the snapshot.
  static const excludedTopLevel = {
    'tenants',
    'android',
    'dist',
    'build',
    'coverage',
    '.dart_tool',
  };

  /// Source files of the project (relative paths). Throws when the project is
  /// not inside a git checkout (the snapshot must be reproducible).
  List<String> sourceFiles() {
    final r = Process.runSync(
      'git',
      [
        'ls-files',
        '-z',
        '--cached',
        '--others',
        '--exclude-standard',
        '--',
        '.',
      ],
      workingDirectory: ws.root,
      stdoutEncoding: utf8,
    );
    if (r.exitCode != 0) {
      throw ToolException(
        'iOS builds use a snapshot of the git working tree; `git ls-files` failed in '
        '${ws.root}: ${r.stderr}',
      );
    }
    final files = <String>{};
    for (final rel in (r.stdout as String).split('\x00')) {
      if (rel.isEmpty) continue;
      final top = rel.split('/').first;
      if (excludedTopLevel.contains(top)) continue;
      if (!File(ws.path(rel)).existsSync()) continue; // deleted, not staged
      files.add(rel);
    }
    return files.toList()..sort();
  }

  /// Makes [appDir] = sources + overlay + the tenant's Dart define file.
  /// Returns what changed (paths relative to the project root).
  GenerateResult sync({List<String>? sources}) {
    final r = GenerateResult();
    final overlay = <String, String>{};
    final od = Directory(overlayDir);
    if (!od.existsSync()) {
      throw ToolException(
        '${ws.rel(overlayDir)} is missing; run generate --tenant $id --platform ios first',
      );
    }
    for (final f in od.listSync(recursive: true).whereType<File>()) {
      overlay[p.relative(f.path, from: overlayDir).replaceAll(r'\', '/')] =
          f.path;
    }
    final hasIconSet = overlay.keys.any(
      (k) => k.startsWith('$iosAppIconSetDir/'),
    );
    final wanted = <String, String>{};
    for (final rel in sources ?? sourceFiles()) {
      if (hasIconSet && rel.startsWith('$iosAppIconSetDir/')) continue;
      wanted[rel] = ws.path(rel);
    }
    wanted.addAll(overlay);
    final define = ws.defineFile(id);
    if (!File(define).existsSync()) {
      throw ToolException('${ws.rel(define)} is missing; run generate first');
    }
    wanted['build/tenant_config/$id.json'] = define;

    final sync = FileSync(ws);
    final keys = wanted.keys.toList()..sort();
    for (final rel in keys) {
      sync.write(p.join(appDir, rel), File(wanted[rel]!).readAsBytesSync(), r);
    }
    // Remove files this tool put there earlier that are gone from the source
    // (build outputs and caches in app/ are not tracked and are kept).
    final previous = File(_managedList).existsSync()
        ? File(_managedList).readAsLinesSync().where((l) => l.isNotEmpty)
        : const <String>[];
    for (final rel in previous) {
      if (wanted.containsKey(rel)) continue;
      final f = File(p.join(appDir, rel));
      if (f.existsSync()) {
        f.deleteSync();
        r.deleted.add(ws.rel(f.path));
      }
    }
    File(_managedList).writeAsStringSync('${keys.join('\n')}\n');
    return r;
  }

  /// Exclusive lock on this tenant's workspace for the life of the process
  /// (two builds of one tenant must not share generated files or build numbers).
  RandomAccessFile lock() {
    Directory(root).createSync(recursive: true);
    final raf = File(p.join(root, '.lock')).openSync(mode: FileMode.write);
    try {
      raf.lockSync(FileLock.exclusive);
    } on FileSystemException {
      raf.closeSync();
      throw ToolException(
        'another build is using ${ws.rel(root)} (tenant $id); wait for it to finish',
      );
    }
    return raf;
  }
}
