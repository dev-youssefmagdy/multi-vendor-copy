import 'dart:convert';
import 'dart:io';

import 'package:args/command_runner.dart';
import 'package:path/path.dart' as p;

import 'generator.dart';
import 'model.dart';
import 'platforms/android.dart';
import 'platforms/ios.dart';
import 'platforms/target.dart';
import 'process.dart';
import 'validator.dart';
import 'workspace.dart';

/// Registered native platforms. Add new targets here.
final Map<String, PlatformTarget> targets = {
  'android': AndroidTarget(),
  'ios': IosTarget(),
};

const usageExit = 64;

/// Entry point used by bin/tenant_builder.dart (and tests).
Future<int> runCli(
  List<String> args, {
  Log? log,
  ProcessRunner? runner,
  Map<String, String>? env,
}) async {
  final l = log ?? Log();
  final r = TenantBuilderRunner(l, runner, env ?? Platform.environment);
  try {
    return await r.run(args) ?? 0;
  } on UsageException catch (e) {
    l.error(e.message);
    l.err.writeln(e.usage);
    return usageExit;
  } on ToolException catch (e) {
    l.error(e.message);
    return e.exitCode;
  }
}

class TenantBuilderRunner extends CommandRunner<int> {
  TenantBuilderRunner(this.log, this.runner, this.env)
    : super(
        'tenant_builder',
        'White-label tenant apps: validate, generate native identity, build, verify.\n'
            'Run from application/: dart run tool/tenant_builder/bin/tenant_builder.dart <command>',
      ) {
    argParser
      ..addOption(
        'root',
        help: 'Flutter project directory (default: found from the current directory).',
      )
      ..addFlag('verbose', abbr: 'v', negatable: false);
    addCommand(ListCommand());
    addCommand(CreateCommand());
    addCommand(ValidateCommand());
    addCommand(GenerateCommand());
    addCommand(RunCommand());
    addCommand(BuildCommand());
    addCommand(VerifyCommand());
    addCommand(UploadCommand());
  }
  final Log log;
  final ProcessRunner? runner;
  final Map<String, String> env;
}

abstract class TenantCommand extends Command<int> {
  TenantBuilderRunner get tb => runner as TenantBuilderRunner;
  Log get log => tb.log;

  Workspace get ws {
    final root = globalResults?['root'] as String?;
    return root != null ? Workspace(root) : Workspace.locate();
  }

  ToolContext context({bool dryRun = false}) => ToolContext(
    ws: ws,
    log: Log(
      out: log.out,
      err: log.err,
      verbose: globalResults?['verbose'] == true,
    ),
    runner: dryRun
        ? DryRunProcessRunner(log.info)
        : (tb.runner ?? const IoProcessRunner()),
    env: tb.env,
    dryRun: dryRun,
  );

  void addTenantOptions({bool allowAll = true}) {
    argParser.addMultiOption(
      'tenant',
      abbr: 't',
      help: 'Tenant id (repeat or comma-separate).',
    );
    if (allowAll) {
      argParser.addFlag(
        'all',
        negatable: false,
        help: 'All enabled tenants (for the platform).',
      );
    }
  }

  void addPlatformOption({bool mandatory = false}) => argParser.addOption(
    'platform',
    abbr: 'p',
    allowed: targets.keys.toList(),
    mandatory: mandatory,
    help: 'Native platform.',
  );

  String? get platformArg => argResults!['platform'] as String?;

  String? get apiBase {
    final v = argResults!.options.contains('api-base')
        ? argResults!['api-base'] as String?
        : null;
    return (v != null && v.isNotEmpty) ? v : tb.env['PLATFORM_API_BASE'];
  }

  void addApiBaseOption() => argParser.addOption(
    'api-base',
    help: 'PLATFORM_API_BASE baked into the app (default: env PLATFORM_API_BASE; required for release).',
  );

  LoadedRegistry load() {
    final loaded = loadRegistry(ws);
    if (loaded.registry == null) {
      for (final e in loaded.errors) {
        log.error('${e.file}: ${e.message}');
      }
      throw ToolException('cannot read tenants/index.json');
    }
    return loaded;
  }

  /// Tenants selected by --tenant / --all (enabled only for --all).
  List<String> selected(Registry reg, {String? platform}) {
    final ids = <String>[
      for (final v in (argResults!['tenant'] as List<String>))
        for (final s in v.split(','))
          if (s.trim().isNotEmpty) s.trim(),
    ];
    final all =
        argResults!.options.contains('all') && argResults!['all'] == true;
    if (all && ids.isNotEmpty) usageException('use either --tenant or --all');
    if (all) {
      return [
        for (final id in reg.enabledIds)
          if (platform == null ||
              (reg.manifests[id]?.enabledFor(platform) ?? false))
            id,
      ];
    }
    if (ids.isEmpty) usageException('--tenant <id> or --all is required');
    return ids;
  }

  /// Validates and prints issues; throws on errors.
  ValidationReport validateOrThrow(LoadedRegistry loaded, ValidationOptions o) {
    final report = Validator(ws).validate(loaded, o);
    for (final w in report.warnings) {
      log.warn('${w.where}: ${w.message}');
    }
    for (final e in report.errors) {
      log.error('${e.where}: ${e.message}');
    }
    if (!report.ok) {
      throw ToolException(
        'validation failed (${report.errors.length} error(s))',
      );
    }
    return report;
  }

  PlatformTarget target(String name) => targets[name]!;
}

// ------------------------------------------------------------------ list --

class ListCommand extends TenantCommand {
  ListCommand() {
    argParser
      ..addFlag(
        'json',
        negatable: false,
        help: 'Machine-readable output (used by CI).',
      )
      ..addFlag('enabled', negatable: false, help: 'Only enabled tenants.');
    addPlatformOption();
  }
  @override
  String get name => 'list';
  @override
  String get description =>
      'List registered tenants and their enabled platforms.';

  @override
  Future<int> run() async {
    final reg = load().registry!;
    final platform = platformArg;
    final rows = <Map<String, Object?>>[];
    for (final e in reg.entries) {
      final m = reg.manifests[e.id];
      if (argResults!['enabled'] == true && !e.enabled) continue;
      if (platform != null && !(m?.enabledFor(platform) ?? false)) continue;
      rows.add({
        'id': e.id,
        'enabled': e.enabled,
        'type': m?.raw['type'],
        'displayName': m?.displayName,
        'android': m != null && m.androidEnabled ? m.applicationId : null,
        'ios': m != null && m.iosEnabled ? m.bundleId : null,
        'storeHost': m?.isPlatform == true ? null : m?.storeHost,
        if (platform != null)
          'environment': platform == 'android'
              ? m?.androidSecretsEnvironment
              : m?.iosSecretsEnvironment,
      });
    }
    if (argResults!['json'] == true) {
      log.info(jsonEncode(rows));
      return 0;
    }
    log.info(
      '${'ID'.padRight(14)}${'ON'.padRight(5)}${'TYPE'.padRight(10)}${'ANDROID'.padRight(34)}IOS',
    );
    for (final r in rows) {
      log.info(
        '${'${r['id']}'.padRight(14)}${(r['enabled'] == true ? 'yes' : 'no').padRight(5)}'
        '${'${r['type']}'.padRight(10)}${'${r['android'] ?? '-'}'.padRight(34)}${r['ios'] ?? '-'}',
      );
    }
    return 0;
  }
}

// ---------------------------------------------------------------- create --

class CreateCommand extends TenantCommand {
  CreateCommand() {
    argParser
      ..addOption('tenant', abbr: 't', mandatory: true, help: 'New tenant id.')
      ..addOption(
        'display-name',
        help: 'App name (default: placeholder to edit).',
      );
  }
  @override
  String get name => 'create';
  @override
  String get description =>
      'Create tenants/<id>/ from tenants/_template and register it (disabled).';

  @override
  Future<int> run() async {
    final id = argResults!['tenant'] as String;
    if (!tenantIdPattern.hasMatch(id) ||
        reservedTenantIds.contains(id) ||
        id.startsWith('test')) {
      throw ToolException(
        'invalid tenant id "$id": must match ^[a-z][a-z0-9]{2,30}\$ and not be reserved',
      );
    }
    final w = ws;
    final dest = Directory(w.tenantDir(id));
    if (dest.existsSync()) {
      throw ToolException(
        '${w.rel(dest.path)} already exists; refusing to overwrite',
      );
    }
    final loaded = load();
    if (loaded.registry!.entry(id) != null) {
      throw ToolException('tenant "$id" is already registered');
    }
    final tpl = Directory(w.templateDir);
    if (!tpl.existsSync()) throw ToolException('tenants/_template is missing');

    for (final f in tpl.listSync(recursive: true).whereType<File>()) {
      final rel = p.relative(f.path, from: tpl.path);
      final out = File(p.join(dest.path, rel));
      out.parent.createSync(recursive: true);
      if (p.basename(f.path) == 'tenant.json') {
        var s = f.readAsStringSync().replaceAll('__TENANT_ID__', id);
        final dn = argResults!['display-name'] as String?;
        if (dn != null) {
          final j = jsonDecode(s) as Map<String, dynamic>..['displayName'] = dn;
          s = '${const JsonEncoder.withIndent('  ').convert(j)}\n';
        }
        out.writeAsStringSync(s);
      } else {
        f.copySync(out.path);
      }
    }
    final index = Map<String, dynamic>.from(loaded.rawIndex!);
    final list = [
      ...(index['tenants'] as List),
      {'id': id, 'enabled': false},
    ];
    index['tenants'] = list;
    File(w.indexFile).writeAsStringSync(encodeIndex(index));
    log.info(
      'Created ${w.rel(dest.path)} (registered as disabled).\n'
      'Next: edit tenants/$id/tenant.json, replace assets/app_icon.png, then\n'
      '  validate --tenant $id   and set "enabled": true in tenants/index.json',
    );
    return 0;
  }
}

/// index.json with one tenant per line (stable, diff-friendly).
String encodeIndex(Map<String, dynamic> index) {
  final b = StringBuffer('{\n');
  final keys = index.keys.where((k) => k != 'tenants').toList();
  for (final k in keys) {
    b.writeln('  ${jsonEncode(k)}: ${jsonEncode(index[k])},');
  }
  b.writeln('  "tenants": [');
  final list = index['tenants'] as List;
  for (var i = 0; i < list.length; i++) {
    final e = list[i] as Map;
    final fields = e.entries
        .map((x) => '${jsonEncode(x.key)}: ${jsonEncode(x.value)}')
        .join(', ');
    b.writeln('    { $fields }${i == list.length - 1 ? '' : ','}');
  }
  b.writeln('  ]');
  b.writeln('}');
  return b.toString();
}

// -------------------------------------------------------------- validate --

class ValidateCommand extends TenantCommand {
  ValidateCommand() {
    addTenantOptions();
    addPlatformOption();
    addApiBaseOption();
    argParser.addFlag(
      'release',
      negatable: false,
      help: 'Release gate: no placeholders, signing + PLATFORM_API_BASE must be available.',
    );
  }
  @override
  String get name => 'validate';
  @override
  String get description =>
      'Validate tenant manifests, assets, ids and (with --release) secrets availability.';

  @override
  Future<int> run() async {
    final loaded = load();
    final ids = argResults!['all'] == true
        ? loaded.registry!.ids
        : selected(loaded.registry!);
    validateOrThrow(
      loaded,
      ValidationOptions(
        tenants: ids.toSet(),
        platform: platformArg,
        release: argResults!['release'] == true,
        env: tb.env,
        apiBase: apiBase,
      ),
    );
    log.info('OK: ${ids.join(', ')}');
    return 0;
  }
}

// -------------------------------------------------------------- generate --

class GenerateCommand extends TenantCommand {
  GenerateCommand() {
    addTenantOptions();
    addPlatformOption();
    addApiBaseOption();
    argParser
      ..addOption(
        'build-name',
        help: 'APP_VERSION define (default: pubspec version).',
      )
      ..addFlag(
        'check',
        negatable: false,
        help: 'Exit 1 if anything would change; write nothing.',
      );
  }
  @override
  String get name => 'generate';
  @override
  String get description =>
      'Write build/tenant_config/<id>.json and native per-tenant files (idempotent).';

  @override
  Future<int> run() async {
    final loaded = load();
    final reg = loaded.registry!;
    final ids = selected(reg);
    validateOrThrow(
      loaded,
      ValidationOptions(
        tenants: ids.toSet(),
        platform: platformArg,
        env: tb.env,
      ),
    );
    final c = context();
    final check = argResults!['check'] == true;
    final version =
        argResults!['build-name'] as String? ?? AppVersion.fromPubspec(ws).name;
    final platforms = platformArg == null
        ? targets.keys.toList()
        : [platformArg!];
    var changed = false;
    for (final id in ids) {
      final m = reg.manifests[id]!;
      final r = GenerateResult();
      generateDartDefines(
        ws,
        FileSync(ws, check: check),
        m,
        r,
        appVersion: version,
        apiBase: apiBase,
      );
      for (final pl in platforms) {
        final pr = await target(pl).generate(c, m, check: check);
        r.written.addAll(pr.written);
        r.deleted.addAll(pr.deleted);
        r.unchanged.addAll(pr.unchanged);
      }
      changed |= r.changed;
      log.info(
        '$id: ${r.written.length} written, ${r.deleted.length} deleted, ${r.unchanged.length} unchanged',
      );
      for (final f in [
        ...r.written.map((f) => '  ${check ? 'would write' : 'wrote'} $f'),
        ...r.deleted.map((f) => '  ${check ? 'would delete' : 'deleted'} $f'),
      ]) {
        log.info(f);
      }
    }
    if (argResults!['all'] == true) {
      for (final pl in platforms) {
        final keep = {
          for (final id in reg.enabledIds)
            if (reg.manifests[id]?.enabledFor(pl) ?? false) id,
        };
        for (final d in target(pl).prune(c, keep, check: check)) {
          changed = true;
          log.info('${check ? 'would remove' : 'removed'} stale $d');
        }
      }
    }
    if (check && changed) {
      log.error('generated files are out of date; run generate');
      return 1;
    }
    return 0;
  }
}

// ------------------------------------------------------------------- run --

class RunCommand extends TenantCommand {
  RunCommand() {
    argParser.addOption('tenant', abbr: 't', mandatory: true);
    addPlatformOption(mandatory: true);
    addApiBaseOption();
  }
  @override
  String get name => 'run';
  @override
  String get description =>
      'flutter run a tenant (extra flutter args after --).';
  @override
  String get invocation =>
      '${runner!.executableName} run --tenant <id> --platform android [-- <flutter run args>]';

  @override
  Future<int> run() async {
    final loaded = load();
    final id = argResults!['tenant'] as String;
    final pl = platformArg!;
    validateOrThrow(
      loaded,
      ValidationOptions(
        tenants: {id},
        platform: pl,
        requireEnabled: true,
        env: tb.env,
      ),
    );
    final m = loaded.registry!.manifests[id]!;
    final c = context();
    final r = GenerateResult();
    generateDartDefines(
      ws,
      FileSync(ws),
      m,
      r,
      appVersion: AppVersion.fromPubspec(ws).name,
      apiBase: apiBase,
    );
    await target(pl).generate(c, m);
    return target(pl).run(c, m, argResults!.rest);
  }
}

// ----------------------------------------------------------------- build --

class BuildCommand extends TenantCommand {
  BuildCommand() {
    addTenantOptions();
    addPlatformOption(mandatory: true);
    addApiBaseOption();
    argParser
      ..addOption(
        'artifact',
        help:
            'aab | apk (android), ipa (ios). Default: first for the platform.',
      )
      ..addOption(
        'mode',
        allowed: ['release', 'profile', 'debug'],
        defaultsTo: 'release',
      )
      ..addOption(
        'build-name',
        help: 'Marketing version (default: pubspec.yaml).',
      )
      ..addOption(
        'build-number',
        help: 'Store build number / versionCode (default: pubspec.yaml).',
      )
      ..addFlag(
        'dry-run',
        negatable: false,
        help: 'Validate + generate, print the build commands only.',
      )
      ..addFlag(
        'skip-verify',
        negatable: false,
        help: 'Do not inspect the artifact after building.',
      );
  }
  @override
  String get name => 'build';
  @override
  String get description =>
      'Build tenant artifacts into dist/<id>/<platform>/ (sequentially for --all).';

  @override
  Future<int> run() async {
    final loaded = load();
    final reg = loaded.registry!;
    final pl = platformArg!;
    final t = target(pl);
    final artifact =
        argResults!['artifact'] as String? ?? t.artifactKinds.first;
    if (!t.artifactKinds.contains(artifact)) {
      usageException(
        '--artifact for $pl must be one of ${t.artifactKinds.join(', ')}',
      );
    }
    final mode = argResults!['mode'] as String;
    final ids = selected(reg, platform: pl);
    if (ids.isEmpty) throw ToolException('no enabled tenant for $pl');
    final base = AppVersion.fromPubspec(ws);
    final numArg = argResults!['build-number'] as String?;
    final override = numArg == null ? null : int.tryParse(numArg);
    if (numArg != null &&
        (override == null || override < 1 || override > 2100000000)) {
      usageException(
        '--build-number must be a positive integer (max 2100000000)',
      );
    }
    final nameArg = argResults!['build-name'] as String?;
    if (nameArg != null && !RegExp(r'^\d+(\.\d+){0,2}$').hasMatch(nameArg)) {
      usageException('--build-name must look like 1.4.0');
    }
    final versionName = nameArg ?? base.name;
    final dry = argResults!['dry-run'] == true;
    final c = context(dryRun: dry);
    final results = <String, String>{};
    for (final id in ids) {
      log.info('\n=== $id ($pl $artifact $mode $versionName) ===');
      try {
        validateOrThrow(
          loaded,
          ValidationOptions(
            tenants: {id},
            platform: pl,
            release: mode == 'release' && !dry,
            requireEnabled: true,
            env: tb.env,
            apiBase: apiBase,
          ),
        );
        final m = reg.manifests[id]!;
        final version = AppVersion(
          versionName,
          await buildNumberFor(c, t, m, mode, override, base.number),
        );
        log.info('$id: version $version');
        final r = GenerateResult();
        generateDartDefines(
          ws,
          FileSync(ws),
          m,
          r,
          appVersion: version.name,
          apiBase: apiBase,
        );
        await t.generate(c, m);
        final out = await t.build(
          c,
          BuildRequest(
            tenant: m,
            registry: reg,
            artifact: artifact,
            mode: mode,
            version: version,
            apiBase: apiBase,
            extraArgs: argResults!.rest,
          ),
        );
        if (!dry && argResults!['skip-verify'] != true) {
          final problems = await t.verify(
            c,
            reg,
            m,
            artifactPath: out.artifactPath,
          );
          if (problems.isNotEmpty) {
            for (final pr in problems) {
              log.error('$id: $pr');
            }
            throw ToolException('verification failed for $id');
          }
        }
        results[id] = 'ok    $version  ${ws.rel(out.artifactPath)}';
      } on NotImplementedYet {
        rethrow;
      } on ToolException catch (e) {
        log.error('$id: ${e.message}');
        results[id] = 'FAILED ${e.message}';
      }
    }
    log.info('\nSummary ($pl $artifact $mode):');
    results.forEach((id, r) => log.info('  ${id.padRight(14)} $r'));
    return results.values.any((r) => r.startsWith('FAILED')) ? 1 : 0;
  }
}

/// Build number policy (PUBLISH_TO_STORES.md Phase 6), per tenant:
///  * release builds ask the tenant's store for its latest build number
///    (fastlane) and use +1;
///  * an explicit `--build-number` wins, but must still be higher than the
///    store's latest when the lookup works;
///  * when the lookup fails and no `--build-number` was given, the build stops
///    (never guess a number that may already be taken);
///  * debug/profile builds and dry runs use pubspec.yaml (or the override).
Future<int> buildNumberFor(
  ToolContext c,
  PlatformTarget t,
  TenantManifest m,
  String mode,
  int? override,
  int pubspecNumber,
) async {
  if (mode != 'release') return override ?? pubspecNumber;
  int? latest;
  try {
    latest = await t.latestStoreBuildNumber(c, m);
  } on ToolException catch (e) {
    if (c.dryRun) {
      c.log.warn(
        '${m.id}: store build-number lookup unavailable: ${e.message}',
      );
      return override ?? pubspecNumber;
    }
    if (override == null) {
      throw ToolException(
        'store build-number lookup failed: ${e.message}. '
        'Pass --build-number N (higher than the store\'s latest) to build anyway.',
      );
    }
    c.log.warn(
      '${m.id}: store build-number lookup failed (${e.message}); using --build-number $override',
    );
    return override;
  }
  if (latest == null) return override ?? pubspecNumber; // dry run
  c.log.info('${m.id}: latest build number in the store: $latest');
  if (override != null) {
    if (override <= latest) {
      throw ToolException(
        '--build-number $override is not higher than the store\'s latest ($latest)',
      );
    }
    return override;
  }
  return latest + 1;
}

// ---------------------------------------------------------------- verify --

class VerifyCommand extends TenantCommand {
  VerifyCommand() {
    argParser
      ..addOption('tenant', abbr: 't', mandatory: true)
      ..addOption(
        'artifact-path',
        help: 'Artifact to inspect (default: last build in dist/).',
      );
    argParser.addOption(
      'platform',
      abbr: 'p',
      allowed: targets.keys.toList(),
      defaultsTo: 'android',
    );
  }
  @override
  String get name => 'verify';
  @override
  String get description =>
      'Inspect a built artifact: ids, label, version, links, icon, signing, no other tenant inside.';

  @override
  Future<int> run() async {
    final reg = load().registry!;
    final id = argResults!['tenant'] as String;
    final m = reg.manifests[id];
    if (m == null) throw ToolException('unknown tenant "$id"');
    final problems = await target(platformArg!).verify(
      context(),
      reg,
      m,
      artifactPath: argResults!['artifact-path'] as String?,
    );
    for (final pr in problems) {
      log.error(pr);
    }
    if (problems.isNotEmpty) return 1;
    log.info('OK: $id artifact verified');
    return 0;
  }
}

// ---------------------------------------------------------------- upload --

class UploadCommand extends TenantCommand {
  UploadCommand() {
    addTenantOptions();
    addPlatformOption(mandatory: true);
    argParser
      ..addOption(
        'track',
        defaultsTo: 'internal',
        help: 'android: internal | alpha | beta (never production).',
      )
      ..addOption(
        'release-status',
        allowed: ['draft', 'completed'],
        defaultsTo: 'draft',
      )
      ..addOption(
        'destination',
        allowed: ['testflight'],
        defaultsTo: 'testflight',
        help: 'ios: testflight (the only destination; no review submission, no release).',
      )
      ..addOption('artifact-path')
      ..addFlag('dry-run', negatable: false);
  }
  @override
  String get name => 'upload';
  @override
  String get description =>
      'Upload the last release build for testing (Play internal track / TestFlight). '
      'Never submits for review, never releases.';

  @override
  Future<int> run() async {
    final reg = load().registry!;
    final pl = platformArg!;
    final ids = selected(reg, platform: pl);
    final c = context(dryRun: argResults!['dry-run'] == true);
    var failed = 0;
    final summary = <String, String>{};
    for (final id in ids) {
      final m = reg.manifests[id];
      if (m == null) throw ToolException('unknown tenant "$id"');
      try {
        final code = await target(pl).upload(
          c,
          m,
          track: pl == 'ios'
              ? argResults!['destination'] as String
              : argResults!['track'] as String,
          releaseStatus: argResults!['release-status'] as String,
          artifactPath: argResults!['artifact-path'] as String?,
        );
        if (code != 0) throw ToolException('upload failed (exit $code)');
        summary[id] = 'ok';
      } on ToolException catch (e) {
        failed++;
        log.error('$id: ${e.message}');
        summary[id] = 'FAILED ${e.message}';
      }
    }
    if (ids.length > 1) {
      log.info('\nUpload summary ($pl):');
      summary.forEach((id, r) => log.info('  ${id.padRight(14)} $r'));
    }
    return failed == 0 ? 0 : 1;
  }
}
