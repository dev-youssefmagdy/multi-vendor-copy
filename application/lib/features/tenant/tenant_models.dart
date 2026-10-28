enum TenantStatus {
  active,
  suspended,
  notFound;

  static TenantStatus parse(Object? v) {
    switch ('$v'.toLowerCase()) {
      case 'active':
        return TenantStatus.active;
      case 'suspended':
        return TenantStatus.suspended;
      default:
        return TenantStatus.notFound;
    }
  }
}

class Tenant {
  const Tenant({
    required this.id,
    required this.domain,
    required this.name,
    this.logo,
    this.status = TenantStatus.active,
    this.apiBase,
  });

  final String id;
  final String domain;
  final String name;
  final String? logo;
  final TenantStatus status;

  /// Informational only; requests always use the platform API origin.
  final String? apiBase;

  factory Tenant.fromJson(Map<String, dynamic> j) => Tenant(
        id: '${j['tenant_id'] ?? j['id']}',
        domain: '${j['domain'] ?? ''}'.toLowerCase(),
        name: '${j['name'] ?? j['domain'] ?? ''}',
        logo: j['logo'] as String?,
        status: TenantStatus.parse(j['status'] ?? 'active'),
        apiBase: j['api_base'] as String?,
      );

  Map<String, dynamic> toJson() => {
        'tenant_id': id,
        'domain': domain,
        'name': name,
        'logo': logo,
        'status': status.name,
        'api_base': apiBase,
      };
}

/// Result of `GET /resolve`.
class ResolveResult {
  const ResolveResult(this.status, [this.tenant]);
  final TenantStatus status;
  final Tenant? tenant;

  factory ResolveResult.fromJson(Map<String, dynamic> j) {
    final status = TenantStatus.parse(j['status']);
    final hasId = (j['tenant_id'] ?? j['id']) != null;
    return ResolveResult(
        status, status != TenantStatus.notFound && hasId ? Tenant.fromJson(j) : null);
  }
}

/// Parsed `GET /bootstrap` payload (tolerant of missing keys).
class TenantConfig {
  const TenantConfig({
    this.appName,
    this.themeSlug = 'default',
    this.variantKey = 'default',
    this.colors = const {},
    this.sections = const [],
    this.features = const {},
    this.locales = const [],
    this.currencies = const [],
    this.maintenance = false,
    this.minVersion,
    this.configVersion,
    this.raw = const {},
  });

  final String? appName;
  final String themeSlug;
  final String variantKey;
  final Map<String, String> colors;
  final List<Map<String, dynamic>> sections;
  final Map<String, dynamic> features;
  final List<String> locales;
  final List<String> currencies;
  final bool maintenance;
  final String? minVersion;
  final String? configVersion;
  final Map<String, dynamic> raw;

  static Map<String, dynamic> _map(Object? v) =>
      v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};

  factory TenantConfig.fromJson(Map<String, dynamic> j) {
    final settings = _map(j['settings']);
    final theme = _map(j['theme']);
    final feats = j['features'];
    return TenantConfig(
      appName: (settings['app_name'] ?? _map(j['tenant'])['name']) as String?,
      themeSlug: '${theme['slug'] ?? j['theme_slug'] ?? 'default'}',
      variantKey: '${theme['variant'] ?? j['variant_key'] ?? 'default'}',
      colors: _map(j['colors']).map((k, v) => MapEntry(k, '$v')),
      sections: [
        for (final s in (j['sections'] as List? ?? const []))
          if (s is Map) Map<String, dynamic>.from(s)
      ],
      features: feats is List
          ? {for (final f in feats) '$f': true}
          : _map(feats),
      locales: [for (final l in (j['locales'] as List? ?? const [])) '$l'],
      currencies: [
        for (final c in (j['currencies'] as List? ?? const []))
          c is Map ? '${c['code']}' : '$c'
      ],
      maintenance: settings['maintenance'] == true || j['maintenance'] == true,
      minVersion: (settings['min_version'] ?? settings['force_update_min_version'])
          as String?,
      configVersion: j['config_version']?.toString(),
      raw: j,
    );
  }

  Map<String, dynamic> toJson() => {
        ...raw,
        'settings': {
          ...TenantConfig._map(raw['settings']),
          'app_name': ?appName,
          'maintenance': maintenance,
          'min_version': ?minVersion,
        },
        'theme': {'slug': themeSlug, 'variant': variantKey},
        'colors': colors,
        'sections': sections,
        'features': features,
        'locales': locales,
        'currencies': currencies,
        'config_version': ?configVersion,
      };
}

class BootstrapResult {
  const BootstrapResult({this.config, this.etag, this.notModified = false});
  final TenantConfig? config;
  final String? etag;
  final bool notModified;
}
