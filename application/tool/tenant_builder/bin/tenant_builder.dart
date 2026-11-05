import 'dart:io';

import '../tenant_builder.dart';

/// `dart run tool/tenant_builder/bin/tenant_builder.dart <command>` (from application/).
Future<void> main(List<String> args) async {
  final code = await runCli(args);
  await stdout.flush();
  await stderr.flush();
  exit(code);
}
