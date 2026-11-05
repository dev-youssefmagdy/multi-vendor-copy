import 'dart:convert';
import 'dart:io';

import 'package:path/path.dart' as p;

import 'platforms/target.dart';
import 'workspace.dart';

/// Runs a lane of the single canonical `application/fastlane/Fastfile` through
/// Bundler (`application/Gemfile`), or `FASTLANE_BIN` when set. Lane options
/// are public identifiers only; credentials go through [environment], which
/// the process runner never prints.
Future<int> runLane(
  ToolContext c,
  String platform,
  String lane,
  Map<String, String> options, {
  Map<String, String> environment = const {},
}) {
  final bin = c.env['FASTLANE_BIN'];
  final exe = (bin != null && bin.isNotEmpty) ? bin : 'bundle';
  return c.runner.run(
    exe,
    [
      if (exe == 'bundle') ...['exec', 'fastlane'],
      platform,
      lane,
      for (final e in options.entries) '${e.key}:${e.value}',
    ],
    workingDirectory: c.ws.root,
    environment: {
      'FASTLANE_SKIP_UPDATE_CHECK': '1',
      'FASTLANE_HIDE_CHANGELOG': '1',
      'FASTLANE_SKIP_DOCS': '1',
      'FASTLANE_OPT_OUT_USAGE': 'YES',
      ...environment,
    },
  );
}

/// Runs a "lookup" lane that writes `{"buildNumber": "<n>"}` to `output:`.
/// Returns null in dry-run mode. Throws [ToolException] when the lane fails or
/// returns something that is not a non-negative integer (fail closed).
Future<int?> lookupBuildNumber(
  ToolContext c,
  String platform,
  String lane,
  Map<String, String> options, {
  Map<String, String> environment = const {},
}) async {
  final tmp = Directory.systemTemp.createTempSync('tenant_builder_lookup');
  try {
    final out = p.join(tmp.path, 'build_number.json');
    final code = await runLane(c, platform, lane, {
      ...options,
      'output': out,
    }, environment: environment);
    if (c.dryRun) return null;
    if (code != 0) {
      throw ToolException('fastlane $platform $lane failed (exit $code)');
    }
    final f = File(out);
    if (!f.existsSync()) {
      throw ToolException('fastlane $platform $lane wrote no result');
    }
    final j = jsonDecode(f.readAsStringSync());
    final raw = j is Map ? '${j['buildNumber']}' : '';
    final n = int.tryParse(raw.trim());
    if (n == null || n < 0) {
      throw ToolException(
        'fastlane $platform $lane returned "$raw", not an integer build number',
      );
    }
    return n;
  } finally {
    tmp.deleteSync(recursive: true);
  }
}
