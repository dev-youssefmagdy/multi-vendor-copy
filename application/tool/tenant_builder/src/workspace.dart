import 'dart:io';

import 'package:path/path.dart' as p;

/// Paths of the Flutter project (`application/`) the tool operates on.
class Workspace {
  Workspace(String root) : root = p.normalize(p.absolute(root));

  /// The Flutter project directory (contains pubspec.yaml and tenants/).
  final String root;

  /// Finds the project from [start] (or the current directory) upwards.
  static Workspace locate([String? start]) {
    var dir = p.absolute(start ?? Directory.current.path);
    while (true) {
      if (File(p.join(dir, 'pubspec.yaml')).existsSync() &&
          Directory(p.join(dir, 'tenants')).existsSync()) {
        return Workspace(dir);
      }
      final parent = p.dirname(dir);
      if (parent == dir) {
        throw StateError(
          'Could not find the Flutter project (pubspec.yaml + tenants/). '
          'Run from application/ or pass --root.',
        );
      }
      dir = parent;
    }
  }

  String path(String rel) => p.join(root, rel);
  String get tenantsDir => path('tenants');
  String get indexFile => p.join(tenantsDir, 'index.json');
  String get templateDir => p.join(tenantsDir, '_template');
  String tenantDir(String id) => p.join(tenantsDir, id);
  String manifestFile(String id) => p.join(tenantDir(id), 'tenant.json');

  /// `--dart-define-from-file` input for one tenant (generated, gitignored).
  String defineFile(String id) => path('build/tenant_config/$id.json');

  /// Generated per-flavor Android source set (gitignored).
  String androidFlavorDir(String id) => path('android/app/src/$id');
  String get androidMainRes => path('android/app/src/main/res');
  String get androidDir => path('android');

  String distDir(String id, String platform) => path('dist/$id/$platform');

  /// Path relative to the project root, for messages.
  String rel(String abs) => p.relative(abs, from: root);
}

/// Console output. Secrets are never passed to it.
class Log {
  Log({IOSink? out, IOSink? err, this.verbose = false})
    : out = out ?? stdout,
      err = err ?? stderr;
  final IOSink out;
  final IOSink err;
  final bool verbose;

  void info(String m) => out.writeln(m);
  void detail(String m) {
    if (verbose) out.writeln(m);
  }

  void warn(String m) => err.writeln('warning: $m');
  void error(String m) => err.writeln('error: $m');
}

/// A failure with a user-facing message and exit code.
class ToolException implements Exception {
  ToolException(this.message, {this.exitCode = 1});
  final String message;
  final int exitCode;
  @override
  String toString() => message;
}

/// Feature that exists in the CLI surface but belongs to a later phase.
class NotImplementedYet extends ToolException {
  NotImplementedYet(super.message) : super(exitCode: 3);
}
