/// Compile-time configuration. Provide with
/// `--dart-define=PLATFORM_API_BASE=https://api.platform.com`.
class Env {
  const Env._();
  static const platformApiBase = String.fromEnvironment(
    'PLATFORM_API_BASE',
    defaultValue: 'https://platform.example.com',
  );
  static const appVersion =
      String.fromEnvironment('APP_VERSION', defaultValue: '1.0.0');
}

/// Runtime view of the configuration (injectable for tests).
class AppConfig {
  AppConfig({
    String platformApiBase = Env.platformApiBase,
    this.appVersion = Env.appVersion,
    this.platform = 'mobile',
    this.connectTimeout = const Duration(seconds: 15),
    this.receiveTimeout = const Duration(seconds: 30),
    this.maxRetries = 2,
  }) : platformApiBase = platformApiBase.replaceAll(RegExp(r'/+$'), '');

  /// ALWAYS the platform's central API origin. Never taken from deep links
  /// or from server-provided `api_base` values.
  final String platformApiBase;
  final String appVersion;
  final String platform;
  final Duration connectTimeout;
  final Duration receiveTimeout;
  final int maxRetries;

  String get apiRoot => '$platformApiBase/api/mobile/v1';
}
