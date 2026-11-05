/// Compile-time configuration. Provide with
/// `--dart-define=PLATFORM_API_BASE=https://api.platform.com`.
///
/// White-label (one app per store) builds get the TENANT_* keys from
/// `build/tenant_config/<id>.json`, written by `tool/tenant_builder` and passed
/// with `--dart-define-from-file`. Without them the binary is the generic
/// `platform` store browser (runtime store picker), exactly as before.
class Env {
  const Env._();
  static const platformApiBase = String.fromEnvironment(
    'PLATFORM_API_BASE',
    defaultValue: 'https://platform.example.com',
  );
  static const appVersion = String.fromEnvironment(
    'APP_VERSION',
    defaultValue: '1.0.0',
  );

  /// Tenant id of this binary (`tenants/<id>`); empty or `platform` = generic app.
  static const tenantId = String.fromEnvironment('TENANT_ID');

  /// Store domain this binary is locked to. Empty = generic store browser.
  static const tenantHost = String.fromEnvironment('TENANT_HOST');

  /// Native app name (splash fallback before the store's bootstrap is cached).
  static const tenantName = String.fromEnvironment('TENANT_NAME');

  /// Comma separated extra hosts of the same store (custom domains).
  static const tenantLinkHosts = String.fromEnvironment('TENANT_LINK_HOSTS');

  /// Custom URL scheme this binary registers (`tenant.json` `urlScheme`), sent as
  /// `X-App-Scheme` so the backend returns payments to this app. Platform app: `storeapp`.
  static const appUrlScheme = String.fromEnvironment(
    'APP_URL_SCHEME',
    defaultValue: 'storeapp',
  );
}

/// Build-time store binding. Immutable for a binary: never read from storage,
/// links or the server.
class TenantBinding {
  const TenantBinding({
    this.id = '',
    this.host = '',
    this.name = '',
    this.linkHosts = const [],
  });

  factory TenantBinding.fromEnvironment() => TenantBinding(
    id: Env.tenantId,
    host: Env.tenantHost.trim().toLowerCase(),
    name: Env.tenantName,
    linkHosts: [
      for (final h in Env.tenantLinkHosts.split(','))
        if (h.trim().isNotEmpty) h.trim().toLowerCase(),
    ],
  );

  /// Generic store-browser build (`platform` tenant or a plain `flutter run`).
  static const none = TenantBinding();

  final String id;
  final String host;
  final String name;
  final List<String> linkHosts;

  /// True when this binary always opens [host] and never offers store entry,
  /// recent stores or "change store".
  bool get isWhiteLabel => host.isNotEmpty;

  /// Hosts that belong to this binary's store (its own domain + custom domains).
  bool ownsHost(String h) {
    final x = h.toLowerCase();
    return x == host || linkHosts.contains(x);
  }

  static final _host = RegExp(
    r'^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$',
  );
  static final _id = RegExp(r'^[a-z][a-z0-9]{2,30}$');

  /// Null when consistent; otherwise why this binary must not start.
  /// [flavor] is Flutter's `appFlavor` (`--flavor`), when known.
  String? validate({String? flavor}) {
    if (id.isNotEmpty && !_id.hasMatch(id)) return 'Invalid TENANT_ID "$id".';
    if (isWhiteLabel) {
      if (id.isEmpty || id == 'platform') {
        return 'TENANT_HOST is set but TENANT_ID is "$id".';
      }
      for (final h in [host, ...linkHosts]) {
        if (!_host.hasMatch(h)) return 'Invalid tenant host "$h".';
      }
    } else if (id.isNotEmpty && id != 'platform') {
      return 'Tenant "$id" was built without TENANT_HOST.';
    }
    // A store flavor must carry its own tenant config (and vice versa).
    if (flavor != null && flavor.isNotEmpty) {
      final boundTo = id.isEmpty ? 'platform' : id;
      if (flavor != boundTo) {
        return 'App flavor "$flavor" was built with the configuration of '
            'tenant "$boundTo". Build with tenant_builder.';
      }
    }
    return null;
  }
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
    this.urlScheme = Env.appUrlScheme,
    TenantBinding? tenant,
  }) : platformApiBase = platformApiBase.replaceAll(RegExp(r'/+$'), ''),
       tenant = tenant ?? TenantBinding.fromEnvironment();

  /// ALWAYS the platform's central API origin. Never taken from deep links
  /// or from server-provided `api_base` values.
  final String platformApiBase;
  final String appVersion;
  final String platform;
  final Duration connectTimeout;
  final Duration receiveTimeout;
  final int maxRetries;

  /// Custom URL scheme of this binary (sent as `X-App-Scheme`).
  final String urlScheme;

  /// Store this binary is locked to (white-label) or [TenantBinding.none].
  final TenantBinding tenant;

  String get apiRoot => '$platformApiBase/api/mobile/v1';
}
