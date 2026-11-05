import 'dart:convert';
import 'dart:io';

import 'package:crypto/crypto.dart';
import 'package:image/image.dart' as img;
import 'package:path/path.dart' as p;

import '../fastlane.dart';
import '../generator.dart';
import '../images.dart';
import '../model.dart';
import '../workspace.dart';
import 'ios_native.dart';
import 'ios_signing.dart';
import 'ios_verify.dart';
import 'ios_workspace.dart';
import 'target.dart';

/// iOS white-label pipeline (PUBLISH_TO_STORES.md Phase 4/6,
/// ios_white_label_release_implementation.md):
///  generate -> `build/workspaces/<id>/overlay` (ios_native.dart)
///  build    -> fastlane build-number lookup + match (read-only), then
///              `flutter build ipa --release --export-options-plist` (no
///              --flavor) inside the isolated snapshot `build/workspaces/<id>/app`
///  verify   -> IPA inspection (ios_verify.dart; + codesign on macOS)
///  upload   -> fastlane pilot to the tenant's own TestFlight. Never submits
///              for review or releases.
class IosTarget extends PlatformTarget {
  @override
  String get name => 'ios';

  @override
  List<String> get artifactKinds => const ['ipa'];

  /// Overridable for tests; real builds/runs need macOS + Xcode.
  bool get hostIsMacOS => Platform.isMacOS;

  static const macOnly =
      'iOS builds need macOS with Xcode (this host is not macOS). On Linux you can run '
      'validate / generate / build --dry-run / verify --artifact-path; real builds run in '
      'the ios-tenant-release workflow (macOS runner).';

  IosWorkspace workspace(Workspace ws, String id) => IosWorkspace(ws, id);

  // ------------------------------------------------------------ generate --

  @override
  Future<GenerateResult> generate(
    ToolContext c,
    TenantManifest t, {
    bool check = false,
  }) async {
    final reg = loadRegistry(c.ws).registry;
    if (reg == null) throw ToolException('cannot read tenants/index.json');
    checkSharedTree(c.ws, reg);
    final r = GenerateResult();
    final w = workspace(c.ws, t.id);
    final files = t.iosEnabled
        ? iosOverlayFiles(t, reg)
        : const <String, List<int>>{};
    final sync = FileSync(c.ws, check: check);
    sync.syncTree(w.overlayDir, files, r);
    if (files.isNotEmpty) {
      sync.writeString(
        w.markerPath,
        '{"generatedBy": "tool/tenant_builder", "tenant": "${t.id}", "platform": "ios"}\n',
        r,
      );
    }
    return r;
  }

  /// The shared working tree must stay the `platform` app: refuse to work when
  /// its AppIdentity.xcconfig carries a store tenant's identity.
  static void checkSharedTree(Workspace ws, Registry reg) {
    final f = File(ws.path('ios/Flutter/AppIdentity.xcconfig'));
    if (!f.existsSync()) return;
    final v = parseXcconfig(f.readAsStringSync());
    for (final m in reg.manifests.values) {
      if (m.isPlatform) continue;
      final hit =
          (v['APP_BUNDLE_ID'] != null && v['APP_BUNDLE_ID'] == m.bundleId)
          ? 'APP_BUNDLE_ID = ${m.bundleId}'
          : (v['APP_URL_SCHEME'] != null && v['APP_URL_SCHEME'] == m.iosScheme)
          ? 'APP_URL_SCHEME = ${m.iosScheme}'
          : null;
      if (hit != null) {
        throw ToolException(
          'ios/Flutter/AppIdentity.xcconfig contains tenant "${m.id}" values ($hit). The shared tree '
          'must stay the platform app; tenant values are only written into build/workspaces/<id>/. '
          'Restore it: git checkout -- ios/Flutter/AppIdentity.xcconfig',
        );
      }
    }
  }

  @override
  List<String> prune(ToolContext c, Set<String> keepIds, {bool check = false}) {
    final dir = Directory(c.ws.path('build/workspaces'));
    final removed = <String>[];
    if (!dir.existsSync()) return removed;
    for (final d in dir.listSync().whereType<Directory>()) {
      final id = p.basename(d.path);
      if (keepIds.contains(id)) continue;
      if (File(p.join(d.path, IosWorkspace.markerName)).existsSync()) {
        removed.add(c.ws.rel(d.path));
        if (!check) d.deleteSync(recursive: true);
      }
    }
    return removed;
  }

  // ------------------------------------------------------------------ run --

  String _defineArg(String id) =>
      '--dart-define-from-file=build/tenant_config/$id.json';

  @override
  Future<int> run(
    ToolContext c,
    TenantManifest t,
    List<String> flutterArgs,
  ) async {
    if (!c.dryRun && !hostIsMacOS) throw ToolException(macOnly);
    final w = workspace(c.ws, t.id);
    final lock = c.dryRun ? null : w.lock();
    try {
      if (!c.dryRun) w.sync();
      return await c.runner.run(c.flutter, [
        'run',
        '-t',
        'lib/main.dart',
        _defineArg(t.id),
        ...flutterArgs,
      ], workingDirectory: w.appDir);
    } finally {
      lock?.closeSync();
    }
  }

  // ---------------------------------------------------------------- build --

  @override
  Future<BuildResult> build(ToolContext c, BuildRequest r) async {
    final t = r.tenant;
    if (r.mode != 'release') {
      throw ToolException(
        'iOS builds produce signed App Store IPAs (--mode release only); '
        'use `run --tenant ${t.id} --platform ios` for debug builds',
      );
    }
    if (!c.dryRun && !hostIsMacOS) throw ToolException(macOnly);
    final bundleId = t.bundleId!;
    final team = t.teamId;
    if (team == null) {
      if (!c.dryRun) {
        throw ToolException('ios.teamId is required to sign ${t.id}');
      }
      c.log.warn('${t.id}: ios.teamId is missing; a real build would fail');
    }
    final creds = resolveIosCredentials(c.ws, t.id, c.env);
    if (!c.dryRun && (creds == null || !creds.hasMatch)) {
      throw ToolException(
        'no signing credentials for ${t.id}: set IOS_SIGNING_TENANT=${t.id} + ${matchKeys.join(', ')} '
        'or create ios/signing/${t.id}.env (see tenants/README.md)',
      );
    }
    final w = workspace(c.ws, t.id);
    final lock = c.dryRun ? null : w.lock();
    try {
      if (c.dryRun) {
        c.log.info(
          '[dry-run] sync ${c.ws.rel(w.appDir)} = project snapshot + ${c.ws.rel(w.overlayDir)}',
        );
      } else {
        final s = w.sync();
        c.log.info(
          'Workspace ${c.ws.rel(w.appDir)}: ${s.written.length} file(s) updated, ${s.deleted.length} removed',
        );
      }

      // Signing: the tenant's own certificate + App Store profile, read-only.
      final m = await runLane(c, 'ios', 'match_readonly', {
        'bundle_id': bundleId,
        'team_id': team ?? 'MISSING_TEAM_ID',
      }, environment: creds?.values ?? const {});
      if (m != 0) {
        throw ToolException(
          'fastlane match (read-only) failed for ${t.id} (exit $m)',
        );
      }

      final ipaDir = Directory(p.join(w.appDir, 'build', 'ios', 'ipa'));
      if (!c.dryRun && ipaDir.existsSync()) ipaDir.deleteSync(recursive: true);
      final code = await c.runner.run(c.flutter, [
        'build',
        'ipa',
        '--release',
        '-t',
        'lib/main.dart',
        _defineArg(t.id),
        '--build-name',
        r.version.name,
        '--build-number',
        '${r.version.number}',
        '--export-options-plist=ExportOptions.plist',
        ...r.extraArgs,
      ], workingDirectory: w.appDir);
      if (code != 0) {
        throw ToolException(
          'flutter build ipa failed for ${t.id} (exit $code)',
        );
      }

      final distDir = c.ws.distDir(t.id, 'ios');
      final dest = p.join(
        distDir,
        '${t.id}-${r.version.name}+${r.version.number}.ipa',
      );
      final infoPath = p.join(distDir, 'build-info.json');
      if (c.dryRun) {
        c.log.info(
          '[dry-run] copy ${c.ws.rel(ipaDir.path)}/*.ipa -> ${c.ws.rel(dest)}; write ${c.ws.rel(infoPath)}',
        );
        return BuildResult(dest, infoPath);
      }
      final ipas = ipaDir.existsSync()
          ? ipaDir
                .listSync()
                .whereType<File>()
                .where((f) => f.path.endsWith('.ipa'))
                .toList()
          : <File>[];
      if (ipas.length != 1) {
        throw ToolException(
          'expected one .ipa in ${c.ws.rel(ipaDir.path)}, found ${ipas.length}',
        );
      }
      Directory(distDir).createSync(recursive: true);
      for (final old in Directory(distDir).listSync().whereType<File>()) {
        if (old.path.endsWith('.ipa')) old.deleteSync();
      }
      ipas.single.copySync(dest);
      final xcode = await c.runner.capture('xcodebuild', ['-version']);
      final git = await _git(c);
      final info = {
        'tenant': t.id,
        'platform': 'ios',
        'artifact': p.basename(dest),
        'artifactType': 'ipa',
        'mode': r.mode,
        'bundleId': bundleId,
        'displayName': t.displayName,
        'teamId': team,
        'appStoreConnectAppId': t.appStoreConnectAppId,
        'storeHost': t.isPlatform ? null : t.storeHost,
        'versionName': r.version.name,
        'buildNumber': r.version.number,
        'gitCommit': git.$1,
        'gitDirty': git.$2,
        'xcode': xcode.exitCode == 0
            ? '${xcode.stdout}'.trim().replaceAll('\n', ' ')
            : null,
        'builtAtUtc': DateTime.now().toUtc().toIso8601String(),
        'sha256': sha256.convert(File(dest).readAsBytesSync()).toString(),
        'signing': 'fastlane match appstore (${matchProfileName(bundleId)})',
      };
      File(infoPath).writeAsStringSync(
        '${const JsonEncoder.withIndent('  ').convert(info)}\n',
      );
      return BuildResult(dest, infoPath);
    } finally {
      lock?.closeSync();
    }
  }

  Future<(String?, bool)> _git(ToolContext c) async {
    try {
      final sha = await c.runner.capture('git', [
        'rev-parse',
        'HEAD',
      ], workingDirectory: c.ws.root);
      final st = await c.runner.capture('git', [
        'status',
        '--porcelain',
        '--',
        '.',
      ], workingDirectory: c.ws.root);
      return (
        sha.exitCode == 0 ? (sha.stdout as String).trim() : null,
        st.exitCode == 0 && (st.stdout as String).trim().isNotEmpty,
      );
    } catch (_) {
      return (null, false);
    }
  }

  // --------------------------------------------------- build-number lookup --

  @override
  Future<int?> latestStoreBuildNumber(ToolContext c, TenantManifest t) async {
    final creds = resolveIosCredentials(c.ws, t.id, c.env);
    if (!c.dryRun && (creds == null || !creds.hasAppStoreConnect)) {
      throw ToolException(
        'no App Store Connect API key for ${t.id} (IOS_SIGNING_TENANT=${t.id} + '
        '${ascKeys.join(', ')}, ASC_PRIVATE_KEY_P8_BASE64 or ASC_KEY_PATH; or ios/signing/${t.id}.env)',
      );
    }
    return lookupBuildNumber(c, 'ios', 'latest_build_number', {
      'bundle_id': t.bundleId!,
    }, environment: creds?.values ?? const {});
  }

  // --------------------------------------------------------------- verify --

  Map<String, dynamic>? buildInfo(Workspace ws, TenantManifest t) {
    final f = File(p.join(ws.distDir(t.id, 'ios'), 'build-info.json'));
    if (!f.existsSync()) return null;
    return jsonDecode(f.readAsStringSync()) as Map<String, dynamic>;
  }

  String? latestArtifact(Workspace ws, TenantManifest t) {
    final j = buildInfo(ws, t);
    return j == null
        ? null
        : p.join(ws.distDir(t.id, 'ios'), j['artifact'] as String);
  }

  /// Reference 1024 icon: the tenant's, or the committed set for `platform`.
  img.Image? referenceIcon(Workspace ws, TenantManifest t) {
    final f = t.appIcon != null
        ? File(p.join(t.dir, t.appIcon!))
        : File(ws.path('$iosAppIconSetDir/Icon-App-1024x1024@1x.png'));
    if (!f.existsSync()) return null;
    try {
      return decodePngFile(f);
    } catch (_) {
      return null;
    }
  }

  IpaExpectations expectations(
    Workspace ws,
    Registry reg,
    TenantManifest t,
    Map<String, dynamic>? info,
  ) => IpaExpectations(
    tenantId: t.id,
    bundleId: t.bundleId ?? '',
    displayName: t.displayName,
    urlSchemes: {?t.iosScheme},
    associatedDomains: iosAssociatedDomainEntitlement(t, reg).toSet(),
    push: t.pushEnabled,
    teamId: t.teamId,
    versionName: info?['versionName'] as String?,
    buildNumber: info?['buildNumber'] as int?,
    icon: referenceIcon(ws, t),
    storeHost: t.isPlatform ? null : t.storeHost,
    release: (info?['mode'] ?? 'release') == 'release',
    otherTenants: [
      for (final o in reg.manifests.values)
        if (o.id != t.id)
          OtherTenant(
            o.id,
            needles: {
              ?o.bundleId,
              ?o.applicationId,
              ...o.linkHosts,
            }.where((n) => n != t.bundleId && n != t.applicationId).toList(),
            icon: referenceIcon(ws, o),
          ),
    ],
  );

  @override
  Future<List<String>> verify(
    ToolContext c,
    Registry reg,
    TenantManifest t, {
    String? artifactPath,
  }) async {
    final path = artifactPath ?? latestArtifact(c.ws, t);
    if (path == null || !File(path).existsSync()) {
      return [
        'no IPA found for ${t.id} (build it first or pass --artifact-path)',
      ];
    }
    c.log.info('Verifying ${c.ws.rel(path)}');
    var info = buildInfo(c.ws, t);
    if (info != null && info['artifact'] != p.basename(path)) info = null;
    final report = verifyIpa(
      File(path).readAsBytesSync(),
      expectations(c.ws, reg, t, info),
    );
    for (final n in report.notes) {
      c.log.info('  $n');
    }
    for (final w in report.warnings) {
      c.log.warn('  $w');
    }
    final problems = [...report.problems];
    if (hostIsMacOS && !c.dryRun) {
      problems.addAll(await _codesign(c, path));
    } else {
      c.log.info(
        '  codesign --verify skipped (needs macOS); signature blob and profile were parsed instead',
      );
    }
    return problems;
  }

  /// `codesign --verify --deep --strict` on the extracted app (macOS only).
  Future<List<String>> _codesign(ToolContext c, String ipa) async {
    final tmp = Directory.systemTemp.createTempSync('tenant_builder_ipa');
    try {
      final unzip = await c.runner.capture('ditto', [
        '-x',
        '-k',
        ipa,
        tmp.path,
      ]);
      if (unzip.exitCode != 0) return ['cannot extract IPA: ${unzip.stderr}'];
      final apps = Directory(p.join(tmp.path, 'Payload'))
          .listSync()
          .where((e) => e.path.endsWith('.app'))
          .toList();
      if (apps.length != 1) return ['IPA has ${apps.length} apps in Payload/'];
      final r = await c.runner.capture('codesign', [
        '--verify',
        '--deep',
        '--strict',
        '--verbose=2',
        apps.single.path,
      ]);
      if (r.exitCode != 0) return ['codesign --verify failed: ${r.stderr}'];
      c.log.info('  codesign --verify --deep --strict: ok');
      return const [];
    } finally {
      tmp.deleteSync(recursive: true);
    }
  }

  // --------------------------------------------------------------- upload --

  @override
  Future<int> upload(
    ToolContext c,
    TenantManifest t, {
    required String track,
    required String releaseStatus,
    String? artifactPath,
  }) async {
    if (track != 'testflight') {
      throw ToolException(
        'iOS uploads go to TestFlight only (--destination testflight). Submitting for review and '
        'releasing are done by the tenant in App Store Connect; this tool never does either.',
      );
    }
    if (!t.iosEnabled) throw ToolException('${t.id}: iOS is not enabled');
    final asc = t.appStoreConnectAppId;
    if (asc == null) {
      throw ToolException(
        '${t.id}: ios.appStoreConnectAppId is required for upload (App Store Connect app record, '
        'PUBLISH_TO_STORES.md §6 step 3)',
      );
    }
    final path = artifactPath ?? latestArtifact(c.ws, t);
    if (path == null ||
        !path.endsWith('.ipa') ||
        (!c.dryRun && !File(path).existsSync())) {
      throw ToolException(
        'no release .ipa for ${t.id}; run build --tenant ${t.id} --platform ios first',
      );
    }
    final reg = loadRegistry(c.ws).registry!;
    if (!c.dryRun) {
      final info = buildInfo(c.ws, t);
      if (info != null &&
          info['artifact'] == p.basename(path) &&
          (info['bundleId'] != t.bundleId || info['mode'] != 'release')) {
        throw ToolException(
          '${c.ws.rel(path)} is not a release build of ${t.bundleId}',
        );
      }
      final problems = await verify(c, reg, t, artifactPath: path);
      if (problems.isNotEmpty) {
        for (final pr in problems) {
          c.log.error('${t.id}: $pr');
        }
        throw ToolException(
          'refusing to upload ${t.id}: IPA verification failed',
        );
      }
    }
    final creds = resolveIosCredentials(c.ws, t.id, c.env);
    if (!c.dryRun && (creds == null || !creds.hasAppStoreConnect)) {
      throw ToolException(
        'no App Store Connect API key for ${t.id}: set IOS_SIGNING_TENANT=${t.id} + '
        '${ascKeys.join(', ')} + ASC_PRIVATE_KEY_P8_BASE64 (or ios/signing/${t.id}.env)',
      );
    }
    return runLane(c, 'ios', 'upload_testflight', {
      'ipa': p.isAbsolute(path) ? path : p.absolute(path),
      'bundle_id': t.bundleId!,
      'app_id': asc,
    }, environment: creds?.values ?? const {});
  }
}

/// `KEY = VALUE` pairs of an xcconfig (comments and includes ignored).
Map<String, String> parseXcconfig(String s) {
  final out = <String, String>{};
  for (final raw in const LineSplitter().convert(s)) {
    final line = raw.split('//').first.trim();
    if (line.isEmpty || line.startsWith('#')) continue;
    final i = line.indexOf('=');
    if (i <= 0) continue;
    out[line.substring(0, i).trim()] = line.substring(i + 1).trim();
  }
  return out;
}
