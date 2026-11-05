import '../generator.dart';
import '../model.dart';
import '../process.dart';
import '../validator.dart';
import '../workspace.dart';

/// Shared services handed to platform targets.
class ToolContext {
  ToolContext({
    required this.ws,
    required this.log,
    required this.runner,
    required this.env,
    this.dryRun = false,
  });
  final Workspace ws;
  final Log log;
  final ProcessRunner runner;
  final Map<String, String> env;
  final bool dryRun;

  String get flutter => env['FLUTTER_BIN'] ?? 'flutter';
}

class BuildRequest {
  BuildRequest({
    required this.tenant,
    required this.registry,
    required this.artifact,
    required this.mode,
    required this.version,
    this.apiBase,
    this.extraArgs = const [],
  });
  final TenantManifest tenant;
  final Registry registry;
  final String artifact;
  final String mode;
  final AppVersion version;
  final String? apiBase;
  final List<String> extraArgs;
}

class BuildResult {
  BuildResult(this.artifactPath, this.buildInfoPath);
  final String artifactPath;
  final String buildInfoPath;
}

/// One native platform (android.dart, ios.dart). New platform work goes into
/// a new subclass + `targets` in cli.dart, not into the commands.
abstract class PlatformTarget {
  String get name;

  /// Artifact kinds for `build --artifact`, first = default.
  List<String> get artifactKinds;

  /// Extra platform rules on top of [Validator] (e.g. toolchain presence).
  List<Issue> validate(
    ToolContext c,
    TenantManifest t, {
    required bool release,
  }) => const [];

  /// Writes the platform's generated native files for [t].
  Future<GenerateResult> generate(
    ToolContext c,
    TenantManifest t, {
    bool check = false,
  });

  /// Removes generated files of tenants no longer enabled. Returns removed paths.
  List<String> prune(
    ToolContext c,
    Set<String> keepIds, {
    bool check = false,
  }) => const [];

  Future<int> run(ToolContext c, TenantManifest t, List<String> flutterArgs);

  Future<BuildResult> build(ToolContext c, BuildRequest r);

  /// Highest build number / versionCode the tenant's store already has
  /// (PUBLISH_TO_STORES.md Phase 6). Null in dry-run mode. Throws
  /// [ToolException] when the lookup is impossible or fails; the caller then
  /// requires an explicit `--build-number` (never guesses).
  Future<int?> latestStoreBuildNumber(ToolContext c, TenantManifest t) =>
      throw ToolException('no store build-number lookup for $name');

  /// Inspects a built artifact; returns problems (empty = verified).
  Future<List<String>> verify(
    ToolContext c,
    Registry reg,
    TenantManifest t, {
    String? artifactPath,
  });

  Future<int> upload(
    ToolContext c,
    TenantManifest t, {
    required String track,
    required String releaseStatus,
    String? artifactPath,
  });
}
