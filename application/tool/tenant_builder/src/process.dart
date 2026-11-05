import 'dart:convert';
import 'dart:io';

/// Runs external tools (flutter, aapt2, fastlane...). Swappable for dry runs
/// and tests. Environment values are never logged, only command lines.
abstract class ProcessRunner {
  /// Streams output to the console; returns the exit code.
  Future<int> run(
    String exe,
    List<String> args, {
    String? workingDirectory,
    Map<String, String>? environment,
  });

  /// Captures output (for inspection tools).
  Future<ProcessResult> capture(
    String exe,
    List<String> args, {
    String? workingDirectory,
  });
}

class IoProcessRunner implements ProcessRunner {
  const IoProcessRunner();

  @override
  Future<int> run(
    String exe,
    List<String> args, {
    String? workingDirectory,
    Map<String, String>? environment,
  }) async {
    final proc = await Process.start(
      exe,
      args,
      workingDirectory: workingDirectory,
      environment: environment,
      mode: ProcessStartMode.inheritStdio,
      runInShell: Platform.isWindows,
    );
    return proc.exitCode;
  }

  @override
  Future<ProcessResult> capture(
    String exe,
    List<String> args, {
    String? workingDirectory,
  }) => Process.run(
    exe,
    args,
    workingDirectory: workingDirectory,
    stdoutEncoding: utf8,
    stderrEncoding: utf8,
    runInShell: Platform.isWindows,
  );
}

/// Prints what would run and pretends it succeeded.
class DryRunProcessRunner implements ProcessRunner {
  DryRunProcessRunner(this.print);
  final void Function(String) print;
  final commands = <List<String>>[];

  @override
  Future<int> run(
    String exe,
    List<String> args, {
    String? workingDirectory,
    Map<String, String>? environment,
  }) async {
    commands.add([exe, ...args]);
    final env = environment == null || environment.isEmpty
        ? ''
        : '${environment.keys.map((k) => '$k=…').join(' ')} ';
    print('[dry-run] $env${shellJoin([exe, ...args])}');
    return 0;
  }

  @override
  Future<ProcessResult> capture(
    String exe,
    List<String> args, {
    String? workingDirectory,
  }) async {
    commands.add([exe, ...args]);
    return ProcessResult(0, 0, '', '');
  }
}

String shellJoin(List<String> parts) => parts
    .map(
      (s) => RegExp(r'^[A-Za-z0-9_./:=+,@%-]+$').hasMatch(s)
          ? s
          : "'${s.replaceAll("'", r"'\''")}'",
    )
    .join(' ');
